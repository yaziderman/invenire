<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

    /*  
       *    Developed by: Syncrypts
       *    Date    : 21 January, 2015
       *    Invenire Stock Inventory Manager
       *    http://codecanyon.net/user/syncrypts
    */

class Crud_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }
    
    // DECLARATION: CREATE A NEW SALE
    function add_new_sale()
    {
        $data['invoice_code']        = $this->input->post('invoice_code');
        $data['discount_percentage'] = $this->input->post('discount_percentage');
        $data['vat_percentage']      = $this->input->post('vat_percentage');
        $data['sub_total']           = $this->input->post('sub_total');
        $data['grand_total']         = $this->input->post('grand_total');
        $data['customer_id']         = $this->input->post('customer_id');
        $data['seller_id']           = $this->input->post('seller_id');
        $data['due']                 = $this->input->post('due');
        $data['timestamp']           = strtotime($this->input->post('timestamp'));
        $invoice_entries             = array();
        $product_ids                 = $this->input->post('product_id');
        $total_numbers               = $this->input->post('total_number');
        $selling_prices              = $this->input->post('selling_price');
        $selling_type_categories     = $this->input->post('selling_type_category');
        $selling_type_ids     = $this->input->post('selling_type_id');
        $customer                    = $this->input->post('customer_id');
        $number_of_entries           = sizeof($product_ids);
        $total_commission            = 0;
        for ($i = 0; $i < $number_of_entries; $i++) {
            if ($product_ids[$i] != "" && $total_numbers[$i] != "" && $selling_prices[$i] != "") {
                $new_entry = array(
                    'product_id' => $product_ids[$i],
                    'total_number' => $total_numbers[$i],
                    'selling_price' => $selling_prices[$i]
                    );
                array_push($invoice_entries, $new_entry);

                $commission_percentage = $this->db->get_where('selling_type', array(
                'id' => $selling_type_ids[$i]))->row()->percentage;

                $cost    =   $selling_prices[$i] - ($selling_prices[$i] * ($data['discount_percentage'] / 100));

                $commission_value     =   $cost * ($commission_percentage / 100);
                $total_commission   +=    $commission_value;


                $product_details = array(
                    'seller_id' => $data['seller_id'],
                    'customer_id' => $data['customer_id'],
                    'invoice_code' => $data['invoice_code'],
                    'product_id' => $product_ids[$i],
                    'total_number' => $total_numbers[$i],
                    'selling_price' => $selling_prices[$i],
                    'selling_type_category' => $selling_type_categories[$i],
                    'selling_type_id' => $selling_type_ids[$i],
                    'vat_percentage' => $data['vat_percentage'],
                    'discount_percentage' => $data['discount_percentage'],
                    'commission_percentage' => $commission_percentage,
                    'commission' => $commission_value,
                    'timestamp' => strtotime(date("m/d/Y")) 
                    );
                
                $this->db->insert('invoice_details', $product_details);


                // DECREASES THE PRODUCT QUANTITY FROM STOCK 
                $this->db->where('product_id', $product_ids[$i]);
                $this->db->set('stock_quantity', 'stock_quantity - ' . $total_numbers[$i], FALSE);
                $this->db->update('product');
            }
        }

        //UPDATE THE MAIN RECORD
        $year  = date('Y');
        $month = date('n');
        $now   = date('Y-m-d');
        $employee_id = $data['seller_id'];

        $seller_already_available    =  $this->db->get_where('sales_log' , array(
            'employee_id' => $employee_id,
            'month'       => $month,
            'year'        => $year,
        ));

        if ($seller = $seller_already_available->row()){
            // $acc_commission = $seller->acc_commission + $total_commission;
            // $number_of_invoices = $seller->number_of_invoices + 1;
            // $number_of_sales = $seller->number_of_sales + $number_of_entries;

            $this->db->where('employee_id', $employee_id);
            $this->db->where('month', $month);
            $this->db->where('year', $year);
            $this->db->set('acc_commission', 'acc_commission + ' . $total_commission, FALSE);
            $this->db->set('number_of_invoices', 'number_of_invoices + 1', FALSE);
            $this->db->set('number_of_sales', 'number_of_sales + ' . $number_of_entries, FALSE);
            $this->db->update('sales_log');
        }else{
             $sales_details = array(
                    'employee_id' => $employee_id,
                    'year' => $year,
                    'month' => $month,
                    'acc_commission' => $total_commission,
                    'number_of_invoices' => 1,
                    'number_of_sales' => $number_of_entries,
                    'last_update' => $now,
                     );            
            $this->db->insert('sales_log', $sales_details);
        }

        $data['invoice_entries'] = json_encode($invoice_entries);
        $this->db->insert('invoice', $data);
        $invoice_id           = $this->db->insert_id();
        // CREATE PAYMENT ENTRY
        $data2['invoice_id']  = $invoice_id;
        $data2['amount']      = $this->input->post('amount');
        $data2['method']      = $this->input->post('method');
        $data2['type']        = 'income';
        $data2['timestamp']   = strtotime(date("Y-m-d H:i:s"));
        $data2['customer_id'] = $this->input->post('customer_id');
        $this->db->insert('payment', $data2);
        
        // MAIL SENDING TO CUSTOMER
        $email_to = $this->db->get_where('customer', array(
            'customer_id' => $data2['customer_id']
        ))->row()->email;
        $this->email_model->sale_notification_email_to_customer($email_to);
        return $invoice_id;
    }

    // DECLARATION: CREATE A NEW PURCHASE
    function new_purchase()
    {
        $data['purchase_code']  =   $this->input->post('purchase_code');
        $data['supplier_id']    =   $this->input->post('supplier_id');
        $data['timestamp']      =   strtotime($this->input->post('timestamp'));
        $purchase_entries       =   array();
        $product_ids            =   $this->input->post('product_id');
        $quantities             =   $this->input->post('quantity');
        $purchase_prices        =   $this->input->post('purchase_price');
        $number_of_entries      =   sizeof($product_ids);
        for ($i = 0; $i < $number_of_entries; $i++) {
            if ($product_ids[$i] != "" && $quantities[$i] != "" && $purchase_prices[$i] != "") {
                $new_purchase_entry =   array(
                    'product_id'     => $product_ids[$i],
                    'quantity'       => $quantities[$i],
                    'purchase_price' => $purchase_prices[$i]
                );
                array_push($purchase_entries , $new_purchase_entry);
                // INCREASES THE PRODUCT QUANTITY IN STOCK 
                $this->db->where('product_id', $product_ids[$i]);
                $this->db->set('stock_quantity', 'stock_quantity + ' . $quantities[$i], FALSE);
                $this->db->update('product');
            }
        }
        $data['purchase_entries']   =   json_encode($purchase_entries);
        $this->db->insert('purchase' , $data);
        $purchase_id    =   $this->db->insert_id();
        // CREATE PAYMENT ENTRY
        $data2['purchase_id']   =   $purchase_id;
        $data2['amount']        =   $this->input->post('amount'); 
        $data2['method']        =   $this->input->post('method'); 
        $data2['type']          =   'expense';
        $data2['timestamp']     =   strtotime($this->input->post('timestamp'));
        $data2['supplier_id']   =   $this->input->post('supplier_id');
        $this->db->insert('payment' , $data2);
        return $purchase_id;
    }

    // DECLARATION: CREATE A NEW ORDER BY ADMIN
    function new_order()
    {
        $data['order_number']        =   $this->input->post('order_number');
        $data['customer_id']         =   $this->input->post('customer_id');
        $data['order_status']        =   $this->input->post('order_status');
        $data['payment_status']      =   $this->input->post('payment_status');
        $data['shipping_address']    =   $this->input->post('shipping_address');
        $data['vat_percentage']      =   $this->input->post('vat_percentage');
        $data['discount_percentage'] =   $this->input->post('discount_percentage');
        $data['sub_total']           =   $this->input->post('sub_total');
        $data['grand_total']         =   $this->input->post('grand_total');
        $data['due']                 =   $this->input->post('due');
        $data['note']                =   $this->input->post('note');
        $data['creating_timestamp']  =   strtotime($this->input->post('creating_timestamp'));
        $order_entries               =   array();
        $product_ids                 =   $this->input->post('product_id');
        $quantities                  =   $this->input->post('quantity');
        $selling_prices              =   $this->input->post('selling_price');
        $number_of_entries           =   sizeof($product_ids);
        for ($i = 0; $i < $number_of_entries; $i++) {
            if ($product_ids[$i] != "" && $quantities[$i] != "" && $selling_prices[$i] != "") {
                $new_order_entry =   array(
                    'product_id'     => $product_ids[$i],
                    'quantity'       => $quantities[$i],
                    'selling_price'  => $selling_prices[$i]
                );
                array_push($order_entries , $new_order_entry);
                // DECREASE THE PRODUCT QUANTITY IN STOCK IF ORDER IS APPROVED
                if ($data['order_status'] == 1) { 
                    $this->db->where('product_id', $product_ids[$i]);
                    $this->db->set('stock_quantity', 'stock_quantity - ' . $quantities[$i], FALSE);
                    $this->db->update('product');
                }
            }
        }
        $data['order_entries']   =   json_encode($order_entries);
        $this->db->insert('order' , $data);
        $order_id    =   $this->db->insert_id();
        if ($this->input->post('amount') != 0) {
            // CREATE PAYMENT ENTRY IF THERE IS ANY AMOUNT PAID
            $data2['order_id']    =   $order_id;
            $data2['amount']      =   $this->input->post('amount'); 
            $data2['type']        =   'income';
            $data2['timestamp']   =   strtotime($this->input->post('creating_timestamp'));
            $data2['customer_id'] =   $this->input->post('customer_id');
            $data2['method']      =   $this->input->post('method');
            $this->db->insert('payment' , $data2);
    }
        return $order_id;
    }

    // DECLARATION: CREATE NEW ORDER FROM CUSTOMER 
    function new_order_from_customer()
    {
        $data['order_number']        =   $this->input->post('order_number');
        $data['customer_id']         =   $this->session->userdata('user_id');
        $data['order_status']        =   0;
        $data['payment_status']      =   0;
        $data['shipping_address']    =   $this->input->post('shipping_address');
        $data['vat_percentage']      =   $this->db->get_where('settings' , array(
            'type' => 'vat_percentage'
        ))->row()->description;
        $data['discount_percentage'] =   $this->db->get_where('customer' , array(
            'customer_id' => $this->session->userdata('user_id')
        ))->row()->discount_percentage;
        $data['note']                =   $this->input->post('note');
        $data['creating_timestamp']  =   strtotime($this->input->post('creating_timestamp'));
        $order_entries               =   array();
        $product_ids                 =   $this->input->post('product_id');
        $quantities                  =   $this->input->post('quantity');
        $selling_prices              =   $this->input->post('selling_price');
        $number_of_entries           =   sizeof($product_ids);
        for ($i = 0; $i < $number_of_entries; $i++) {
            if ($product_ids[$i] != "" && $quantities[$i] != "" && $selling_prices[$i] != "") {

                $selling_price       =   $this->db->get_where('product' , array(
                    'product_id' => $product_ids[$i]
                ))->row()->selling_price;
                $new_order_entry =   array(
                    'product_id'     => $product_ids[$i],
                    'quantity'       => $quantities[$i],
                    'selling_price'  => $selling_price
                );
                array_push($order_entries , $new_order_entry);
            }
        }
        $data['order_entries']   =   json_encode($order_entries);
        // calculate sub total for the entered products
        $inserted_product_infos  =   json_decode($data['order_entries']);
        $sub_total  =   0;
        foreach ($inserted_product_infos as $info) {
            $sub_total += $info->selling_price;
        }
        $data['sub_total']  =   $sub_total;
        // calculate grand total for the entered products
        $vat                 =      $data['vat_percentage'] / 100;
        $discount            =      $data['discount_percentage'] / 100;
        $grand_total         =      ($sub_total * $vat) + $sub_total;
        $grand_total         =      $grand_total - ($grand_total * $discount);
        $data['grand_total'] =      $grand_total;
        $data['due']         =      $grand_total;
        $this->db->insert('order' , $data);
        $order_id    =   $this->db->insert_id();
        return $order_id;
    }


    // DECLARATION: SEND NEW MESSAGE
    function send_new_message()
    {
        $message_body = $this->input->post('message_body');
        $receiver     = $this->input->post('receiver');
        $sender       = $this->session->userdata('login_type') . '-' . $this->session->userdata('user_id');
        $query1       = $this->db->get_where('message_thread', array(
            'sender' => $sender,
            'receiver' => $receiver
        ))->num_rows();
        $query2       = $this->db->get_where('message_thread', array(
            'sender' => $receiver,
            'receiver' => $sender
        ))->num_rows();
        if ($query1 == 0 && $query2 == 0) {
            $message_thread_code                        = substr(md5(rand(100000000, 20000000000)), 0, 15);
            $data_message_thread['message_thread_code'] = $message_thread_code;
            $data_message_thread['sender']              = $sender;
            $data_message_thread['receiver']            = $receiver;
            $this->db->insert('message_thread', $data_message_thread);
        }
        if ($query1 > 0)
            $message_thread_code = $this->db->get_where('message_thread', array(
                'sender' => $sender,
                'receiver' => $receiver
            ))->row()->message_thread_code;
        if ($query2 > 0)
            $message_thread_code = $this->db->get_where('message_thread', array(
                'sender' => $receiver,
                'receiver' => $sender
            ))->row()->message_thread_code;
        $timestamp                           = strtotime(date("Y-m-d H:i:s"));
        $data_message['message_thread_code'] = $message_thread_code;
        $data_message['message_body']        = $message_body;
        $data_message['sender']              = $sender;
        $data_message['timestamp']           = $timestamp;
        $this->db->insert('message', $data_message);
        return $message_thread_code;
    }

    // DECLARATION: SEND REPLY MESSAGE
    function send_reply_message($message_thread_code)
    {
        $message_body                        = $this->input->post('message_body');
        $timestamp                           = strtotime(date("Y-m-d H:i:s"));
        $sender                              = $this->session->userdata('login_type') . '-' . $this->session->userdata('user_id');
        $data_message['message_thread_code'] = $message_thread_code;
        $data_message['message_body']        = $message_body;
        $data_message['sender']              = $sender;
        $data_message['timestamp']           = $timestamp;
        $this->db->insert('message', $data_message);
    }

    // DECLARATION: GET ANY IMAGE LOCATION OF USERS
    function get_image_url($type = '', $id = '')
    {
        if (file_exists('uploads/' . $type . '_image/' . $id . '.jpg'))
            $image_url = base_url() . 'uploads/' . $type . '_image/' . $id . '.jpg';
        else
            $image_url = base_url() . 'uploads/avatar.png';
        return $image_url;
    }

    // DECLARATION: GET ANY IMAGE LOCATION OF PRODUCTS
    function get_image_url_object($type = '', $id = '')
    {
        if (file_exists('uploads/' . $type . '_image/' . $id . '.jpg'))
            $image_url = base_url() . 'uploads/' . $type . '_image/' . $id . '.jpg';
        else
            $image_url = base_url() . 'uploads/none.png';
        return $image_url;
    }
}

/* End of file Crud_model.php */
/* Location: ./application/models/Crud_model.php */