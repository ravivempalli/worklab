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
$invoice_csv = "invoice.csv";
$invoice_item_csv = "invoice_item.csv";
$invoice_comment_csv = "invoice_comment.csv";
$shipment_csv = "shipment.csv";
$shipment_item_csv = "shipment_item.csv";
$shipment_comment_csv = "shipment_comment.csv";
$shipment_track_csv = "shipment_track.csv";

$shipment_fp = fopen($shipment_csv, "r");
$shipment_track_fp = fopen($shipment_track_csv, "r");
$shipment_item_fp = fopen($shipment_item_csv, "r");
$shipment_comment_fp = fopen($shipment_comment_csv, "r");

$shipment_columns = fgetcsv($shipment_fp);
/* read the invoice items */
$shipment_items = array();
$shipment_item_columns = fgetcsv($shipment_item_fp);
while($line = fgetcsv($shipment_item_fp)){
  $shipment_id_index = array_search('parent_id', $shipment_item_columns);
  $shipment_id = $line[$shipment_id_index];
  if(!isset($shipment_items[$shipment_id])){
    $shipment_items[$shipment_id] = array();
  }
  
  $shipment_item_data = array();
  foreach($shipment_item_columns as $i=>$col){
    $shipment_item_data[$col] = $line[$i];
  }
  $shipment_items[$shipment_id][] = $shipment_item_data;
}
fclose($shipment_item_fp);
/* read the shipment comments */
$shipment_comments = array();
$shipment_comment_columns = fgetcsv($shipment_comment_fp);
while($line = fgetcsv($shipment_comment_fp)){
  $shipment_id_index = array_search('parent_id', $shipment_comment_columns);
  $shipment_id = $line[$shipment_id_index];
  if(!isset($shipment_comments[$shipment_id])){
    $shipment_comments[$shipment_id] = array();
  }
  
  $shipment_comment_data = array();
  foreach($shipment_comment_columns as $i=>$col){
    $shipment_comment_data[$col] = $line[$i];
  }
  $shipment_comments[$shipment_id][] = $shipment_comment_data;
}
fclose($shipment_comment_fp);
/* read the shipment tracks */
$shipment_tracks = array();
$shipment_track_columns = fgetcsv($shipment_track_fp);
while($line = fgetcsv($shipment_track_fp)){
  $shipment_id_index = array_search('parent_id', $shipment_track_columns);
  $shipment_id = $line[$shipment_id_index];
  if(!isset($shipment_tracks[$shipment_id])){
    $shipment_tracks[$shipment_id] = array();
  }
  
  $shipment_track_data = array();
  foreach($shipment_track_columns as $i=>$col){
    $shipment_track_data[$col] = $line[$i];
  }
  $shipment_tracks[$shipment_id][] = $shipment_track_data;
}
fclose($shipment_track_fp);
while($line = fgetcsv($shipment_fp)){
  $order_id_index = array_search('order_id', $shipment_columns);
  $order_id = $line[$order_id_index];
  $order = Mage::getModel('sales/order')
           ->load($order_id, "increment_id");
  if(!$order || !$order->getId()){
     echo "\tCould not import shipment for order " . $order_id . "\n";
     continue;
  }
  $line[$order_id_index] = $order->getId();
  $shipment_data = array();
  foreach($shipment_columns as $i=>$col){
    $shipment_data[$col] = $line[$i];
  }
  $shipment = Mage::getModel('sales/order_shipment');
  $shipment->setData($shipment_data);
  $shipment_id = $shipment->getId();
  $shipment->setId(NULL);
echo "Creating shipment\n";
  /* get all the shipment items - shipment items map to order_item_id which
     we are not sure of. So, we will match sku. Problem is single order has
     multiple sku - we will map the same item */
  foreach($shipment_items[$shipment_id] as $shipment_item_data){
    $shipment_item = Mage::getModel('sales/order_shipment_item');
    $shipment_item->setData($shipment_item_data);
    $shipment_item->setId(NULL);
    $shipment_item->setParentId(NULL);
    foreach($order->getAllItems() as $item){
      if($item->getSku() == $shipment_item->getSku()){
        $shipment_item->setOrderItem($item);
        break;
      }
    }
echo "\tadding shipment item\n";
    $shipment->addItem($shipment_item);
  }

  /* add comments */
  foreach($shipment_comments[$shipment_id] as $shipment_comment_data){
echo "\tadding shipment comment\n";
    $shipment->addComment($shipment_comment_data['comment'],
                         $shipment_comment_data['is_customer_notified'],
                         $shipment_comment_data['is_visible_on_front']);
  }
  $shipment->save();
  /* add tracks */
  foreach($shipment_tracks[$shipment_id] as $shipment_track_data){
    $shipment_track = Mage::getModel('sales/order_shipment_track');
    $shipment_track->setData($shipment_track_data);
    $shipment_track->setId(NULL);
    $shipment_track->setParentId(NULL);
    $shipment_track->setOrderId($shipment->getOrderId());
    
echo "\tadding shipment track\n";
    $shipment->addTrack($shipment_track);
  }
  $shipment->save();
}
fclose($shipment_fp);


$invoice_fp = fopen($invoice_csv, "r");
$invoice_item_fp = fopen($invoice_item_csv, "r");
$invoice_comment_fp = fopen($invoice_comment_csv, "r");
$invoice_columns = fgetcsv($invoice_fp);
/* read the invoice items */
$invoice_items = array();
$invoice_item_columns = fgetcsv($invoice_item_fp);
while($line = fgetcsv($invoice_item_fp)){
  $invoice_id_index = array_search('parent_id', $invoice_item_columns);
  $invoice_id = $line[$invoice_id_index];
  if(!isset($invoice_items[$invoice_id])){
    $invoice_items[$invoice_id] = array();
  }
  
  $invoice_item_data = array();
  foreach($invoice_item_columns as $i=>$col){
    $invoice_item_data[$col] = $line[$i];
  }
  $invoice_items[$invoice_id][] = $invoice_item_data;
}
fclose($invoice_item_fp);
/* read the invoice comments */
$invoice_comments = array();
$invoice_comment_columns = fgetcsv($invoice_comment_fp);
while($line = fgetcsv($invoice_comment_fp)){
  $invoice_id_index = array_search('parent_id', $invoice_comment_columns);
  $invoice_id = $line[$invoice_id_index];
  if(!isset($invoice_comments[$invoice_id])){
    $invoice_comments[$invoice_id] = array();
  }
  
  $invoice_comment_data = array();
  foreach($invoice_comment_columns as $i=>$col){
    $invoice_comment_data[$col] = $line[$i];
  }
  $invoice_comments[$invoice_id][] = $invoice_comment_data;
}
fclose($invoice_comment_fp);
while($line = fgetcsv($invoice_fp)){
  $order_id_index = array_search('order_id', $invoice_columns);
  $order_id = $line[$order_id_index];
  $order = Mage::getModel('sales/order')
           ->load($order_id, "increment_id");
  if(!$order || !$order->getId()){
     echo "\tCould not import invoice for order " . $order_id . "\n";
     continue;
  }
  $line[$order_id_index] = $order->getId();
  $invoice_data = array();
  foreach($invoice_columns as $i=>$col){
    $invoice_data[$col] = $line[$i];
  }
  $invoice = Mage::getModel('sales/order_invoice');
  $invoice->setData($invoice_data);
  $invoice_id = $invoice->getId();
  $invoice->setId(NULL);
  /* get all the invoice items - invoice items map to order_item_id which
     we are not sure of. So, we will match sku. Problem is single order has
     multiple sku - we will map the same item */
  foreach($invoice_items[$invoice_id] as $invoice_item_data){
    $invoice_item = Mage::getModel('sales/order_invoice_item');
    $invoice_item->setData($invoice_item_data);
    $invoice_item->setId(NULL);
    $invoice_item->setParentId(NULL);
    foreach($order->getAllItems() as $item){
      if($item->getSku() == $invoice_item->getSku()){
        $invoice_item->setOrderItem($item);
        break;
      }
    }
    $invoice->addItem($invoice_item);
  }

  /* add comments */
  foreach($invoice_comments[$invoice_id] as $invoice_comment_data){
    $invoice->addComment($invoice_comment_data['comment'],
                         $invoice_comment_data['is_customer_notified'],
                         $invoice_comment_data['is_visible_on_front']);
  }
  $invoice->save();

}
fclose($invoice_fp);

