<?php
/* (c) Rrap Software Pvt Ltd.
   Order export utility.
   Usage :
   php export_order.php -t <from_time>
   More filters will be added later on.
   Creates the orders, items, etc in individual csv files in the same directory where it is invoked from.
   WARNING : Will overwrite the files
*/
require_once("../app/Mage.php");
Mage::app('default');

/* TODO : Get this from the comamnd line */
$greaterValue = '2014-09-29';

/* TODO : Give control on the name of the csv or the directory where they will go. */
$order_csv = "order.csv";
$order_item_csv = "order_item.csv";
$quote_csv = "quote.csv";
$quote_item_csv = "quote_item.csv";
$oa_csv = "oa.csv";
$op_csv = "op.csv";
$osh_csv = "osh.csv";
$shipment_csv = "shipment.csv";
$shipment_item_csv = "shipment_item.csv";
$shipment_comment_csv = "shipment_comment.csv";
$shipment_track_csv = "shipment_track.csv";
$invoice_csv = "invoice.csv";
$invoice_item_csv = "invoice_item.csv";
$invoice_comment_csv = "invoice_comment.csv";

/* Open the csv files - TODO : Warn if already exist, give a force flag */
$order_fp = fopen($order_csv, "w");
$order_item_fp = fopen($order_item_csv, "w");
$quote_fp = fopen($quote_csv, "w");
$quote_item_fp = fopen($quote_item_csv, "w");
$oa_fp = fopen($oa_csv, "w");
$op_fp = fopen($op_csv, "w");
$osh_fp = fopen($osh_csv, "w");
$shipping_fp = fopen($shipping, "w");
/* Get the sales order collection with the filter */
$model = Mage::getModel('sales/order');
$orderCollection = $model->getCollection()
              ->addAttributeToSelect('entity_id')
              ->addFieldToFilter('created_at', array("gt" => $greaterValue));

/* csv will have the first row as the names of the columns - which will be the names of the data items from the table */
$order_firsttime = true;
$quote_firsttime = true;
$oa_firsttime = true;
$op_firsttime = true;
$osh_firsttime = true;
foreach ($orderCollection as $order) {
   $order = Mage::getModel('sales/order')->load($order->getId());
   $order_items = $order->getItemsCollection();
   $data = $order->getData();
   $item = $order_items->getFirstItem();
   $item_data = $item->getData();
   $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
   $q_data = $quote->getData();
   $quote_items = $quote->getItemsCollection();
   $quote_item = $quote_items->getFirstItem();
   $qi_data = $quote_item->getData();

   if($order_firsttime){
     $order_firsttime = false;
     $comma = "";
     foreach($data as $e => $v){
       fprintf($order_fp, $comma . '"' . $e . '"');
       $comma = ",";
     }
     fprintf($order_fp, "\n");
     $comma = "";
     foreach($item_data as $e => $v){
       fprintf($order_item_fp, $comma . '"' . $e . '"');
       $comma = ",";
     }
     fprintf($order_item_fp, "\n");
   }

   /* generate the actual data lines */
echo "\nGenerating Order " . $order->getId() . " ";
   $comma = "";
   foreach($data as $e => $v){
     fprintf($order_fp, $comma . '"' . $v . '"');
     $comma = ",";
   }
   fprintf($order_fp, "\n");
   foreach($order_items as $item){
echo "\n\tItems " . $item->getId() . " ";
     $item_data = $item->getData();
     $comma = "";
     foreach($item_data as $e => $v){
       $v = str_replace('"', '\\"', $v);
       fprintf($order_item_fp, $comma . '"' . $v . '"');
       $comma = ",";
     }
     fprintf($order_item_fp, "\n");
   }
  if(!$quote->getId()){
   echo "\n\tQUOTE NOT FOUND";
  }else{
     if($quote_firsttime){
       $quote_firsttime = false;
       fprintf($order_item_fp, "\n");
       $comma = "";
       foreach($q_data as $e => $v){
         fprintf($quote_fp, $comma . '"' . $e . '"');
         $comma = ",";
       }
       fprintf($quote_fp, "\n");
       $comma = "";
       foreach($qi_data as $e => $v){
         fprintf($quote_item_fp, $comma . '"' . $e . '"');
         $comma = ",";
       }
       fprintf($quote_item_fp, "\n");
     }
  echo "\n\tQuote " . $quote->getId() . " ";
     $comma = "";
     foreach($q_data as $e => $v){
       fprintf($quote_fp, $comma . '"' . $v . '"');
       $comma = ",";
     }
     fprintf($quote_fp, "\n");
     foreach($quote_items as $item){
  echo "\n\tQuote Items " . $item->getId() . " ";
       $item_data = $item->getData();
       $comma = "";
       foreach($item_data as $e => $v){
         $v = str_replace('"', '\\"', $v);
         fprintf($quote_item_fp, $comma . '"' . $v . '"');
         $comma = ",";
       }
       fprintf($quote_item_fp, "\n");
     }
  }

  /* generate sales flat order address */
  $oa = Mage::getModel('sales/order_address')
        ->load($order->getShippingAddressId());
  $oa_data = $oa->getData();
  if($oa_firsttime){
     $oa_firsttime = false;
     $comma = "";
     foreach($oa_data as $e => $v){
       fprintf($oa_fp, $comma . '"' . $e . '"');
       $comma = ",";
     }
     fprintf($oa_fp, "\n");
   }

   /* generate the actual data lines */
echo "\n\tOrder Address " . $oa->getId() . " ";
   $comma = "";
   foreach($oa_data as $e => $v){
     fprintf($oa_fp, $comma . '"' . $v . '"');
     $comma = ",";
   }
   fprintf($oa_fp, "\n");
  $oa = Mage::getModel('sales/order_address')
        ->load($order->getBillingAddressId());
  $oa_data = $oa->getData();
   /* generate the actual data lines */
echo "\n\tOrder Address " . $oa->getId() . " ";
   $comma = "";
   foreach($oa_data as $e => $v){
     fprintf($oa_fp, $comma . '"' . $v . '"');
     $comma = ",";
   }
   fprintf($oa_fp, "\n");
   
  /* generate sales flat order payment */
  $op = Mage::getModel('sales/order_payment')
        ->load($order->getId(), 'parent_id');
  $op_data = $op->getData();
  if($op_firsttime){
     $op_firsttime = false;
     $comma = "";
     foreach($op_data as $e => $v){
       fprintf($op_fp, $comma . '"' . $e . '"');
       $comma = ",";
     }
     fprintf($op_fp, "\n");
   }

   /* generate the actual data lines */
echo "\n\tOrder Payment " . $op->getId() . " ";
   $comma = "";
   foreach($op_data as $e => $v){
     fprintf($op_fp, $comma . '"' . $v . '"');
     $comma = ",";
   }
   fprintf($op_fp, "\n");

  /* generate sales flat order status history */
  $oshs = Mage::getModel('sales/order_status_history')
         ->getCollection()
         ->addFieldToFilter('parent_id', $order->getId());
  foreach($oshs as $osh){
    $osh_data = $osh->getData();
    if($osh_firsttime){
       $osh_firsttime = false;
       $comma = "";
       foreach($osh_data as $e => $v){
         fprintf($osh_fp, $comma . '"' . $e . '"');
         $comma = ",";
       }
       fprintf($osh_fp, "\n");
     }

     /* generate the actual data lines */
echo "\n\tOrder Payment Status" . $osh->getId() . " ";
     $comma = "";
     foreach($osh_data as $e => $v){
       fprintf($osh_fp, $comma . '"' . $v . '"');
       $comma = ",";
     }
     fprintf($osh_fp, "\n");
  }
}
fclose($order_fp);
fclose($order_item_fp);
fclose($quote_fp);
fclose($quote_item_fp);
fclose($oa_fp);
fclose($op_fp);
fclose($osh_fp);

/* Export invoice, invoice_item, invoice_comment */
$invoice_fp = fopen($invoice_csv, "w");
$invoice_item_fp = fopen($invoice_item_csv, "w");
$invoice_comment_fp = fopen($invoice_comment_csv, "w");

$invoice = Mage::getModel('sales/order_invoice');
$invoiceCollection = $invoice->getCollection()
              ->addFieldToFilter('created_at', array("gt" => $greaterValue))
              ->addFieldToFilter('updated_at', array("gt" => $greaterValue));
$invoice_firsttime = true;
$invoice_item_firsttime = true;
$invoice_comment_firsttime = true;
foreach($invoiceCollection as $inv){
  $order = Mage::getModel('sales/order')
           ->load($inv->getOrderId());
  $inv->setOrderId($order->getIncrementId());
  $invoice_data = $inv->getData();
  if($invoice_firsttime){
       $invoice_firsttime = false;
       $comma = "";
       foreach($invoice_data as $e => $v){
         fprintf($invoice_fp, $comma . '"' . $e . '"');
         $comma = ",";
       }
       fprintf($invoice_fp, "\n");
  }

  /* generate the actual data lines */
echo "\nInvoice " . $inv->getId() . " ";
  $comma = "";
  foreach($invoice_data as $e => $v){
       fprintf($invoice_fp, $comma . '"' . $v . '"');
       $comma = ",";
  }
  fprintf($invoice_fp, "\n");

  $inv_items = $inv->getAllItems();
  foreach($inv_items as $inv_item){
    $invoice_item_data = $inv_item->getData();
    if($invoice_item_firsttime){
      $invoice_item_firsttime = false;
      $comma = "";
      foreach($invoice_item_data as $e => $v){
         fprintf($invoice_item_fp, $comma . '"' . $e . '"');
         $comma = ",";
      }
      fprintf($invoice_item_fp, "\n");
    }
    /* generate the actual data lines */
echo "\n\tInvoice Item " . $inv_item->getId() . " ";
    $comma = "";
    foreach($invoice_item_data as $e => $v){
       fprintf($invoice_item_fp, $comma . '"' . $v . '"');
       $comma = ",";
    }
    fprintf($invoice_item_fp, "\n");
  }
  $inv_comments = $inv->getCommentsCollection();
  foreach($inv_comments as $inv_comment){
    $invoice_comment_data = $inv_comment->getData();
    if($invoice_comment_firsttime){
      $invoice_comment_firsttime = false;
      $comma = "";
      foreach($invoice_comment_data as $e => $v){
         fprintf($invoice_comment_fp, $comma . '"' . $e . '"');
         $comma = ",";
      }
      fprintf($invoice_comment_fp, "\n");
    }
    /* generate the actual data lines */
echo "\n\tInvoice Comment " . $inv_comment->getId() . " ";
    $comma = "";
    foreach($invoice_comment_data as $e => $v){
       fprintf($invoice_comment_fp, $comma . '"' . $v . '"');
       $comma = ",";
    }
    fprintf($invoice_comment_fp, "\n");
  }
}
fclose($invoice_fp);
fclose($invoice_item_fp);
fclose($invoice_comment_fp);

/* Export shipment, shipment_item, shipment_comment, shipment_track*/
$shipment_fp = fopen($shipment_csv, "w");
$shipment_item_fp = fopen($shipment_item_csv, "w");
$shipment_comment_fp = fopen($shipment_comment_csv, "w");
$shipment_track_fp = fopen($shipment_track_csv, "w");
$shipment = Mage::getModel('sales/order_shipment');
$shipmentCollection = $shipment->getCollection()
              ->addFieldToFilter('created_at', array("gt" => $greaterValue))
              ->addFieldToFilter('updated_at', array("gt" => $greaterValue));
$shipment_firsttime = true;
$shipment_item_firsttime = true;
$shipment_comment_firsttime = true;
$shipment_track_firsttime = true;
foreach($shipmentCollection as $ship){
  $order = Mage::getModel('sales/order')
           ->load($ship->getOrderId());
  $ship->setOrderId($order->getIncrementId());
  $shipment_data = $ship->getData();
  if($shipment_firsttime){
       $shipment_firsttime = false;
       $comma = "";
       foreach($shipment_data as $e => $v){
         fprintf($shipment_fp, $comma . '"' . $e . '"');
         $comma = ",";
       }
       fprintf($shipment_fp, "\n");
  }

  /* generate the actual data lines */
echo "\nShipment " . $ship->getId() . " ";
  $comma = "";
  foreach($shipment_data as $e => $v){
       fprintf($shipment_fp, $comma . '"' . $v . '"');
       $comma = ",";
  }
  fprintf($shipment_fp, "\n");
  $ship_items = $ship->getAllItems();
  foreach($ship_items as $ship_item){
    $shipment_item_data = $ship_item->getData();
    if($shipment_item_firsttime){
      $shipment_item_firsttime = false;
      $comma = "";
      foreach($shipment_item_data as $e => $v){
         fprintf($shipment_item_fp, $comma . '"' . $e . '"');
         $comma = ",";
      }
      fprintf($shipment_item_fp, "\n");
    }
    /* generate the actual data lines */
echo "\n\tShipment Item " . $ship_item->getId() . " ";
    $comma = "";
    foreach($shipment_item_data as $e => $v){
       fprintf($shipment_item_fp, $comma . '"' . $v . '"');
       $comma = ",";
    }
    fprintf($shipment_item_fp, "\n");
  }
  $ship_comments = $ship->getCommentsCollection();
  foreach($ship_comments as $ship_comment){
    $shipment_comment_data = $ship_comment->getData();
    if($shipment_comment_firsttime){
      $shipment_comment_firsttime = false;
      $comma = "";
      foreach($shipment_comment_data as $e => $v){
         fprintf($shipment_comment_fp, $comma . '"' . $e . '"');
         $comma = ",";
      }
      fprintf($shipment_comment_fp, "\n");
    }
    /* generate the actual data lines */
echo "\n\tShipment Comment " . $ship_comment->getId() . " ";
    $comma = "";
    foreach($shipment_comment_data as $e => $v){
       fprintf($shipment_comment_fp, $comma . '"' . $v . '"');
       $comma = ",";
    }
    fprintf($shipment_comment_fp, "\n");
  }
  $ship_tracks = $ship->getTracksCollection();
  foreach($ship_tracks as $ship_track){
    $shipment_track_data = $ship_track->getData();
    if($shipment_track_firsttime){
      $shipment_track_firsttime = false;
      $comma = "";
      foreach($shipment_track_data as $e => $v){
         fprintf($shipment_track_fp, $comma . '"' . $e . '"');
         $comma = ",";
      }
      fprintf($shipment_track_fp, "\n");
    }
    /* generate the actual data lines */
echo "\n\tShipment Track " . $ship_track->getId() . " ";
    $comma = "";
    foreach($shipment_track_data as $e => $v){
       fprintf($shipment_track_fp, $comma . '"' . $v . '"');
       $comma = ",";
    }
    fprintf($shipment_track_fp, "\n");
  }
}
