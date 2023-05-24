<?php

class Shopify {
	
	public  $getUrl       = '';
	private $token        = '';
 
    function createOrder($data = array()) {
        $url = $this->getUrl.'orders.json';
        $response = $this->sendRequest($method = 'POST',$url,  $data);
        return $response;
    }
    
    function getAllProducts($since_id = 0) {
        $products = array();
        
        do {
            $params = http_build_query(array(
                'since_id' => $since_id,
                'limit' => 250
            ));
           
            $url = $this->getUrl.'products.json?'.$params;
            $response = $this->sendRequest($method = 'GET',$url,  $data = array());
 
            if (isset($response['products'])) {
                $products = array_merge($products, $response['products']);
                if (!empty($response['products'])) {
                    $since_id = end($response['products'])['id'];
                }
            } else {
                // Handle error
                echo 'Error: Could not retrieve products';
                break;
            }
        } while (!empty($response['products']));
        
        return $products;
    }
    

    function findCustomerByEmail ($email) {
        $url = $this->getUrl.'customers/search.json?query=email:'.$email;
    
        $response = $this->sendRequest($method = 'GET',$url,  $data = array());
        return $response['customers'][0] ?? [];
    }

    function getCustomersWithPagination($params = array()) {

        $customers = array();
        $pagination = array();

        $url = $this->getUrl.'customers.json?'.http_build_query($params);
        $response = $this->sendRequestPagination($method = 'GET',$url,  $data = array() ,"customers");
     
        if (isset($response['datas'])) {
            $customers = $response['datas'];
        } else {
            // Handle error
            echo 'Error: Could not retrieve customers';
            return;
        }
    
        $pagination = $response['pagination'];
 
        return array(
            'customers' => $customers,
            'pagination' => $pagination
        );
    }
    
    // orders
    function getOrdersWithPagination($params = array()) {
        $orders = array();
        $pagination = array();
         
        $url = $this->getUrl.'orders.json?'.http_build_query($params);
        $response = $this->sendRequestPagination($method = 'GET',$url,  $data = array() ,"orders");
    
        if (isset($response['datas'])) {
            $orders = $response['datas'];
        } else {
            // Handle error
            echo 'Error: Could not retrieve orders';
            return;
        }
    
        $pagination = $response['pagination'];
    
        return array(
            'orders' => $orders,
            'pagination' => $pagination
        );
    }

    // products

    function getProductsWithPagination($params = array()) {
        $products = array();
        $pagination = array();
    
        $url = $this->getUrl.'products.json?'.http_build_query($params);
        $response = $this->sendRequestPagination($method = 'GET',$url,  $data = array(),"products");
    
        if (isset($response['datas'])) {
            $products = $response['datas'];
        } else {
            // Handle error
            echo 'Error: Could not retrieve products';
            return;
        }
    
        $pagination = $response['pagination'];
    
        return array(
            'products' => $products,
            'pagination' => $pagination
        );
    }


    function parseLinkHeader($headers) {
        $pagination = array();
    
        if (isset($headers['link']) || isset($headers['Link'])) {
            $link_header = isset($headers['link']) ? $headers['link'] : $headers['Link'];
            $links = explode(',', $link_header);
    
            foreach ($links as $link) {
                preg_match('/<([^>]+)>; rel="([^"]+)"/', $link, $matches);
                $url = @$matches[1];
                $rel = @$matches[2];
    
                $page_info = null;
                if (preg_match('/page_info=([^&]+)/', $url, $matches)) {
                    $page_info = $matches[1];
                }
    
                $pagination[$rel] = $page_info;
            }

           
        }
    
        return $pagination;
    }
    
 
    function sendRequest($method, $url, $data = array()) {
        $headers = array(
            'Content-Type: application/json',
            'X-Shopify-Access-Token: '.$this->token
        );
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // Error:SSL certificate problem: unable to get local issuer certificate
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
 
    
        if ($method == 'POST' || $method == 'PUT') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
    
        $response = json_decode($result, true);
        return $response;
    }
    
    function sendRequestPagination($method, $url, $data = array() ,$type = 'customers') {
        $headers = array(
            'Content-Type: application/json',
            'X-Shopify-Access-Token: ' . $this->token
        );


    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // Başlıkları alma
        curl_setopt($ch, CURLOPT_HEADER, true);
        // Error:SSL certificate problem: unable to get local issuer certificate
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
        if ($method == 'POST' || $method == 'PUT') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    
        $response = curl_exec($ch);
    
        // Başlıkları ve içeriği ayırma
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
    
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
    
        $response_data = json_decode($body, true);
    
        // Başlıkları işleme
        $header_rows = explode(PHP_EOL, $header);

        

        $response_headers = array();
        foreach ($header_rows as $row) {
            if (strpos($row, ':') !== false) {
                list($key, $value) = explode(':', $row, 2);
                $response_headers[$key] = trim($value);
            }
        }

        $pagination = $this->parseLinkHeader($response_headers);
    
        return array(
 
            'datas' =>  $response_data[$type],
            'pagination' => $pagination
        );
    }
    
    function getAllCustomers() {
        $all_customers = array();
        $since_id = 0;

        // eğer productt.json dosyası varsa onu oku

        if (file_exists('customers.json') && filesize('customers.json') > 0) {

            $all_customers = json_decode(file_get_contents('customers.json'), true);
         
        }else{
        
            while (true) {
                $params = array(
                    'limit' => 250,
                    'since_id' => $since_id
                );
        
                $customers = $this->getCustomers($params);
                if (count($customers) == 0) {
                    break;
                }
        
                $all_customers = array_merge($all_customers, $customers);
                $last_customer = end($customers);
                $since_id = $last_customer['id'];
            }

            // bunu json oalrak kaydet
            file_put_contents('customers.json', json_encode($all_customers));
        }
    
        return $all_customers;
    }
    
    function getCustomers($params = array()) {
        $url = $this->getUrl.'customers.json?'.http_build_query($params);
        $response = $this->sendRequest('GET', $url);
        return $response['customers'];
    }
    
    function getOrders($params = array()) {

        $url = $this->getUrl.'orders.json?'.http_build_query($params);
        $response = $this->sendRequest('GET', $url);


        return $response['orders'];
    }
    
    function getProducts($params = array()) {
        $url = $this->getUrl.'products.json?'.http_build_query($params);
        $response = $this->sendRequest('GET', $url);
        return $response['products'];
    }


    function getOrderDetails($order_id) {
        $url = $this->getUrl.'orders/'.$order_id.'.json';
        $response = $this->sendRequest('GET', $url);
        return $response['order'];
    }
    
    function getProductDetails($product_id) {
        $url = $this->getUrl.'products/'.$product_id.'.json';
        $response = $this->sendRequest('GET', $url);
        return $response['product'];
    }

    function getCustomerDetails($customer_id) {
        $url = $this->getUrl.'customers/'.$customer_id.'.json';
        $response = $this->sendRequest('GET', $url);
        return $response['customer'];
    }
    
    function getLastCustomers($limit = 100) { 
        $params = array(
            'limit' => $limit,
            'status' => 'any'
        );
    
        $orders = $this->getCustomers($params);
        return $orders;   
    }

    function getLastOrders($limit = 100) { 
        $params = array(
            'limit' => $limit,
            'status' => 'any'
        );
    
        $orders = $this->getOrders($params);
        return $orders;   
    }


    function getTotalCustomerCount() {
        $countUrl = $this->getUrl.'customers/count.json';
        $countResponse = $this->sendRequest('GET', $countUrl);
        return $countResponse['count'];
    }

    function getTotalOrderCount() {
        $countUrl = $this->getUrl.'orders/count.json?status=any';
        $countResponse = $this->sendRequest('GET', $countUrl);
        return $countResponse['count'];
    }

    function getTotalProductCount() {
        $countUrl = $this->getUrl.'products/count.json';
        $countResponse = $this->sendRequest('GET', $countUrl);
        return $countResponse['count'];
    }

    function getFullOrderDetails($order_id) {
        $url = $this->getUrl.'orders/'.$order_id.'.json';
        $response = $this->sendRequest('GET', $url);
        $order = $response['order'];
    
        // Ürünler
        $products = array();
        foreach ($order['line_items'] as $line_item) {
            $product_id = $line_item['product_id'];
            $product = $this->getProductDetails($product_id);
            $products[] = array(
                'id' => $product['id'],
                'title' => $product['title'],
                'sku' => $line_item['sku'],
                'quantity' => $line_item['quantity'],
                'price' => $line_item['price'],
                'total' => $line_item['total']
            );
        }
    
        // Müşteri bilgileri
        $customer_id = $order['customer']['id'];
        $customer = $this->getCustomerDetails($customer_id);
    
        // Ödeme bilgileri
        $payment = $order['payment_details'];
    
        // Ödeme tipi
        $payment_type = $payment['credit_card_company'];
    
        // Kargo bilgileri
        $shipping = $order['shipping_address'];
    
        // Kargo tipi
        $shipping_type = $order['shipping_lines'][0]['title'];
    
        // İndirimler
        $discounts = $order['discount_codes'];
    
        // Fatura adresi
        $billing = $order['billing_address'];
    
        $order_details = array(
            'order' => $order,
            'products' => $products,
            'customer' => $customer,
            'payment' => $payment,
            'payment_type' => $payment_type,
            'shipping' => $shipping,
            'shipping_type' => $shipping_type,
            'discounts' => $discounts,
            'billing' => $billing
        );
    
        return $order_details;
    }



    function getOrderCountByDateRange($start_date, $end_date) {
        $params = array(
            'created_at_min' => $start_date,
            'created_at_max' => $end_date
        );
        $orders = $this->getOrders($params);
        return count($orders);
    }

    function getOrderCountByDate() {
        try {

            $onedayago   = date('Y-m-d', strtotime('-1 day'));
            $onedaylater =  date('Y-m-d', strtotime('+1 day'));
            $params = array(
                'created_at_min' => $onedayago . '',
                'created_at_max' => $onedaylater . ''
            );
            $orders = $this->getOrders($params);
            return count($orders);
            return $orders;
        } catch (Exception $e) {
            // Handle any exceptions that might be thrown by the getOrders method.
            error_log($e->getMessage());
            return false;
        }
    }


    function stats() {
        $stats = array();
      
        // Get total number of customers, orders, and products
        $stats['total_customers'] = $this->getTotalCustomerCount();
        $stats['total_orders']    = $this->getTotalOrderCount();
        $stats['total_products']  = $this->getTotalProductCount();
      
        // Get today's orders
       
        $stats['today_orders'] = $this->getOrderCountByDate();
      
        // Get this month's orders
        $month_start = date('Y-m-01');
        $month_end   = date('Y-m-t');
        $stats['this_month_orders'] = $this->getOrderCountByDateRange($month_start, $month_end);
      
        // Get this year's orders
        $year_start = date('Y-01-01');
        $year_end   = date('Y-12-31');
        $stats['this_year_orders'] = $this->getOrderCountByDateRange($year_start, $year_end);
      
        return $stats;
      }
      

      public function customerPasswordReset($customer_id) {
        $url = $this->getUrl . 'customers/' . $customer_id . '/account_activation_url.json';

        $data = array(
            "account_activation" => array(
                "subject" => "Şifre Sıfırlama E-postası",
                "body" => "Lütfen aşağıdaki bağlantıya tıklayarak şifrenizi sıfırlayın."
            )
        );

        $response = $this->sendRequest('POST', $url, $data);
        return $response;
    }


    public function send_invite ($customer_id) {
        $url = $this->getUrl . 'customers/' . $customer_id . '/send_invite.json';

        $data = array(
            "customer_invite" => array(
                "subject" => "Şifre Sıfırlama E-postası",
                "body" => "Lütfen aşağıdaki bağlantıya tıklayarak şifrenizi sıfırlayın."
            )
        );

        $response = $this->sendRequest('POST', $url, $data);
        return $response;
    }
    

}   
 
?>

