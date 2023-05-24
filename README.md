# shopify-api-php
Shopif api php

# Shopify API Sınıfı

Bu PHP sınıfı, Shopify mağazanıza erişmek ve çeşitli işlemleri gerçekleştirmek için kullanılabilir. Shopify API'sini kullanarak siparişleri, ürünleri, müşterileri ve daha fazlasını alabilir ve yönetebilirsiniz.

## Kurulum

- `Shopify.php` dosyasını indirin ve projenizin klasörüne yerleştirin.
- Dosyanın başına `require 'Shopify.php';` ekleyin.

## Kullanım

```php
// Shopify nesnesini oluşturun
$shopify = new Shopify();

// Sipariş oluşturma
$orderData = array(
    // Sipariş verilerini buraya ekleyin
);
$orderResponse = $shopify->createOrder($orderData);
print_r($orderResponse);

// Tüm ürünleri al
$products = $shopify->getAllProducts();
print_r($products);

// E-postaya göre müşteri arama
$email = 'example@example.com';
$customer = $shopify->findCustomerByEmail($email);
print_r($customer);

// Sayfalama ile müşteri listesini al
$customerParams = array(
    // Sayfalama parametrelerini buraya ekleyin
);
$customerData = $shopify->getCustomersWithPagination($customerParams);
print_r($customerData['customers']);
echo 'Sonraki Sayfa: ' . $customerData['pagination']['next'];

// Sayfalama ile sipariş listesini al
$orderParams = array(
    // Sayfalama parametrelerini buraya ekleyin
);
$orderData = $shopify->getOrdersWithPagination($orderParams);
print_r($orderData['orders']);
echo 'Sonraki Sayfa: ' . $orderData['pagination']['next'];

// Sayfalama ile ürün listesini al
$productParams = array(
    // Sayfalama parametrelerini buraya ekleyin
);
$productData = $shopify->getProductsWithPagination($productParams);
print_r($productData['products']);
echo 'Sonraki Sayfa: ' . $productData['pagination']['next'];

