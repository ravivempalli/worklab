<?php
/* (c) Rrap Software Pvt Ltd.
   Order import utility.
   Usage :
   php export_import.php 
   Creates the orders, items, etc in individual csv files in the same directory where it is invoked from.
   WARNING : Will overwrite orders with same id already present
*/
require_once("../app/Mage.php");
Mage::app('default');

/* TODO : Give control on the name of the csv or the directory where they will go. */
$order_csv = "order.csv";
$order_item_csv = "order_item.csv";
$quote_csv = "quote.csv";
$quote_item_csv = "quote_item.csv";
$oa_csv = "oa.csv";
$op_csv = "op.csv";
$osh_csv = "osh.csv";

/* Open the csv files - TODO : Warn if already exist, give a force flag */
$quote_fp = fopen($quote_csv, "r");
$quote_item_fp = fopen($quote_item_csv, "r");

/* open and read the csv files.
   Index by the fk in each table that maps to the order table.
   We will need to substitute the values appropriately.
   We will have fake references to tables we do not populate - custmoer_address_id for example
   TODO : fix all holes in related tables
*/

/* Read all other other csv files ... other than order */
$op_fp = fopen($op_csv, "r");
$payments = array();
$payment_columns = fgetcsv($op_fp);
while($line = fgetcsv($op_fp)){
  $order_id_index = array_search('parent_id', $payment_columns);
  $order_id = $line[$order_id_index];
  $payments[$order_id] = array();
  foreach($payment_columns as $i=>$col){
    $payments[$order_id][$col] = $line[$i];
  }
}
fclose($op_fp);

$osh_fp = fopen($osh_csv, "r");
$oshs = array();
$osh_columns = fgetcsv($osh_fp);
while($line = fgetcsv($osh_fp)){
  $order_id_index = array_search('parent_id', $osh_columns);
  $order_id = $line[$order_id_index];
  if(!isset($oshs[$order_id])){
    $oshs[$order_id] = array();
  }
  $osh_data = array();
  foreach($osh_columns as $i=>$col){
    $osh_data[$col] = $line[$i];
  }
  $oshs[$order_id][] = $osh_data;
}
fclose($osh_fp);

$order_item_fp = fopen($order_item_csv, "r");
$order_items = array();
$oi_columns = fgetcsv($order_item_fp);
while($line = fgetcsv($order_item_fp)){
  $order_id_index = array_search('order_id', $oi_columns);
  $order_id = $line[$order_id_index];
  if(!isset($order_items[$order_id])){
    $order_items[$order_id] = array();
  }
  
  $order_item_data = array();
  foreach($oi_columns as $i=>$col){
    $order_item_data[$col] = $line[$i];
  }
  $order_items[$order_id][] = $order_item_data;
}
fclose($order_item_fp);

/* Read the order address csv and index by parent id */
$oa_fp = fopen($oa_csv, "r");
$addresses = array(); // index by old address id, store all addresses with common parent id in an array
$oa_columns = fgetcsv($oa_fp);
while($line = fgetcsv($oa_fp)){
  $address_data = array();
  foreach($oa_columns as $i=>$col){
    $address_data[$col] = $line[$i];
  }
  $addresses[$address_data['entity_id']] = $address_data;
}
fclose($oa_fp);

/* read the orders, find corresponding maps and insert */
$order_fp = fopen($order_csv, "r");
$orders = array();
$order_columns = fgetcsv($order_fp);
while($line = fgetcsv($order_fp)){
  $order_id_index = array_search('entity_id', $order_columns);
  $order_id = $line[$order_id_index];
echo "order id is " . $order_id . "\n";
  $this_oi = $order_items[$order_id];
  $order = Mage::getModel('sales/order');
  $order_data = array();
  foreach($order_columns as $i=>$col){
if($col == 'customer_id'){
if($line[$i] != 0){
echo "setting customer_id \n";
    #$order_data[$col] = $line[$i];
}
}
else{
    $order_data[$col] = $line[$i];
}
  }
  $order->setData($order_data);
  $order->setId(NULL);

  if($order->getCustomerId() != NULL){
echo "customer id is " . $order->getCustomerId() . "\n";
    $customer = Mage::getModel('customer/customer')
                  ->load($order->getCustomerId());
    print_r($customer);
  }else{
$order->unsetCustomerId();
  }

  $payment = Mage::getModel('sales/order_payment')
             ->setData($payments[$order_id])
             ->setParentId(NULL)
             ->setId(NULL);
  $order->setPayment($payment);

  /* Set the address */
  $billing_address_data = $addresses[$order_data['billing_address_id']];
echo "billing address id " . $order_data['billing_address_id'] . "\n";
  $shipping_address_data = $addresses[$order_data['shipping_address_id']];
echo "shipping address id " . $order_data['shipping_address_id'] . "\n";
  
  $billing_address = Mage::getModel('sales/order_address')
                     ->setData($billing_address_data)
                     ->setId(NULL)
                     ->unsParentId()
                     ;
  $shipping_address = Mage::getModel('sales/order_address')
                     ->setData($shipping_address_data)
                     ->setId(NULL)
                     ->unsParentId()
                     ;
  $order->unsBillingAddressId();
  $order->unsShippingAddressId();
  $order->setShippingAddress($shipping_address);
  $order->setBillingAddress($billing_address);
  
  $transaction = Mage::getModel('core/resource_transaction');
  $transaction->addObject($order);
  $transaction->addCommitCallback(array($order, 'save'));

  $transaction->save();
  $new_order_id = $order->getId();
  echo "old order id was " . $order_id . " new order id is " . $new_order_id . "\n";
  foreach($this_oi as $oi_data){
    echo "\tcreating order item" . $oi_data['item_id'] . "\n";
    $oi = Mage::getModel('sales/order_item');
    $oi->setData($oi_data);
    $oi->setId(NULL);
    $oi->setOrderId($new_order_id);
    $oi->save();
  }
  $this_osh = $oshs[$order_id];
  foreach($this_osh as $osh_data){
    $osh = Mage::getModel('sales/order_status_history');
    $osh->setdata($osh_data);
    $osh->setId(NULL);
    $osh->setParentId($new_order_id);
    $osh->save();
  }
}

