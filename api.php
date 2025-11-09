<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

class OTPAPI {
    private $api_key = 'eyJhbGciOiJSUzUxMiIsInR5cCI6IkpXVCJ9.eyJleHAiOjE3OTQyMDk4NjQsImlhdCI6MTc2MjY3Mzg2NCwicmF5IjoiOTMwN2EwYTk3ZTBlODEzYjM1NzJkMWJlNGI5YzFlNjQiLCJzdWIiOjMwOTA0Mzl9.DSBVHlWaSFPHLdfRYquEXaI5-1K7DFMG7s7N4W3qHumEy7YYpfmnG6RxgaIgOwgFlGsu4JPA4P_fUw6JvTizOhlbo75TT2-H1Z-8cF44meKBa0jr1CjvjEmwV3a6okY02UgEDGx9fHQIRegBNCG2okHoCWJWBJ1RCUu-vCcDl7c4CAQagNmCkfDNzx8JXDG3iHYir_gOROzxf9HUlC5dbzNze9IhuPce64SLGPls60wfD2W8D4XeoNc1uay0KqmcdWGrACn1zLNzFZPnYqs5cWtByLRwFqN-kwm1BwtqI8mP-ZK5-66qqhakP7K96N0ocmFGLnCa-5a4e0fTMTGQZw';
    private $base_url = 'https://5sim.net/v1/';
    
    // Price markup - 1.5x to 1.75x
    private $price_multiplier = 1.75;
    
    public function __construct() {
        $this->handleRequest();
    }
    
    private function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        switch($path) {
            case '/api/services':
                $this->getServices();
                break;
            case '/api/order':
                if ($method === 'POST') {
                    $this->placeOrder();
                }
                break;
            case preg_match('/\/api\/sms\/(\d+)/', $path, $matches) ? true : false:
                $this->getSMS($matches[1]);
                break;
            case '/api/balance':
                $this->getBalance();
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
        }
    }
    
    private function getServices() {
        try {
            // Get products from 5SIM
            $products = $this->call5SIM('products');
            
            $services = [];
            foreach ($products as $country => $categories) {
                foreach ($categories as $category => $service) {
                    if (isset($service['price'])) {
                        $original_price = $service['price'];
                        $our_price = $this->applyMarkup($original_price);
                        
                        $services[] = [
                            'id' => $category . '_' . $country,
                            'name' => ucfirst($category) . ' - ' . strtoupper($country),
                            'original_price' => $original_price,
                            'price' => $our_price,
                            'country' => $country,
                            'service' => $category
                        ];
                    }
                }
            }
            
            echo json_encode(array_slice($services, 0, 20)); // Limit to 20 services
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    private function placeOrder() {
        $input = json_decode(file_get_contents('php://input'), true);
        $service = $input['service'];
        
        list($service_type, $country) = explode('_', $service);
        
        try {
            // Place order with 5SIM
            $order = $this->call5SIM('guest/buy/activation/' . $country . '/' . $service_type . '/any');
            
            // Calculate our price
            $our_price = $this->applyMarkup($order['price']);
            
            $response = [
                'success' => true,
                'order' => [
                    'id' => $order['id'],
                    'number' => $order['phone'],
                    'price' => $our_price,
                    'service' => $service_type,
                    'country' => $country
                ]
            ];
            
            echo json_encode($response);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    private function getSMS($order_id) {
        try {
            $sms_data = $this->call5SIM('guest/check/' . $order_id);
            
            if (isset($sms_data['sms']) && !empty($sms_data['sms'])) {
                $latest_sms = end($sms_data['sms']);
                echo json_encode(['success' => true, 'sms' => $latest_sms['text']]);
            } else {
                echo json_encode(['success' => true, 'sms' => null]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    private function getBalance() {
        // For demo - in real scenario, get from database
        echo json_encode(['success' => true, 'balance' => 500.00]);
    }
    
    private function call5SIM($endpoint) {
        $url = $this->base_url . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->api_key,
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception('API request failed: ' . $response);
        }
        
        return json_decode($response, true);
    }
    
    private function applyMarkup($original_price) {
        // Convert from USD to INR and apply markup
        $usd_to_inr = 83; // Current exchange rate
        $price_inr = $original_price * $usd_to_inr;
        return ceil($price_inr * $this->price_multiplier);
    }
}

// Initialize API
new OTPAPI();
?>
