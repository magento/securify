<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaReview\Test\Api\GraphQl\Review;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * GraphQl test for ProductReview functionality with ReCaptcha.
 */
class ProductReviewsTest extends GraphQlAbstract
{
    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->customerTokenService = $objectManager->get(CustomerTokenServiceInterface::class);
        $this->productRepository = $objectManager->get(ProductRepositoryInterface::class);
    }

    /**
     * @param string $customerName
     * @param bool $isGuest
     * @return void
     *
     * @magentoApiDataFixture Magento/Review/_files/set_position_and_add_store_to_all_ratings.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     *
     * @magentoConfigFixture default_store customer/captcha/enable 0
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/public_key test_public_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/private_key test_private_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/product_review invisible
     *
     * @dataProvider customerDataProvider
     */
    public function testAddProductReviewReCaptchaValidationFailed(string $customerName, bool $isGuest): void
    {
        $productSku = 'simple_product';
        $query = $this->getQuery($productSku, $customerName);
        $headers = [];

        if (!$isGuest) {
            $headers = $this->getHeaderMap();
        }

        $this->expectExceptionMessage('ReCaptcha validation failed, please try again');
        $this->graphQlMutation($query, [], '', $headers);
    }

    /**
     * @return array
     */
    public static function customerDataProvider(): array
    {
        return [
            'Guest' => ['John Doe', true],
            'Customer' => ['John', false],
        ];
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return array
     */
    private function getHeaderMap(string $username = 'customer@example.com', string $password = 'password'): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($username, $password);

        return ['Authorization' => 'Bearer ' . $customerToken];
    }

    /**
     * Get mutation query
     *
     * @param string $sku
     * @param string $customerName
     *
     * @return string
     */
    private function getQuery(string $sku, string $customerName): string
    {
        return <<<QUERY
mutation {
  createProductReview(
    input: {
      sku: "$sku",
      nickname: "$customerName",
      summary: "Summary Test",
      text: "Text Test",
      ratings: [
        {
          id: "Mw==",
          value_id: "MTM="
        }, {
          id: "MQ==",
          value_id: "Mg=="
        }, {
          id: "Mg==",
          value_id: "MTA="
        }
      ]
    }
) {
    review {
      nickname
      summary
      text
      average_rating
      ratings_breakdown {
        name
        value
      }
    }
  }
}
QUERY;
    }
}
