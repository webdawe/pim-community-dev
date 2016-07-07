@javascript
Feature: Pin pages to pinbar
  In order to navigate easily between pages
  As a regular user
  I need to be able to pin pages

  Background:
    Given a "default" catalog configuration
    And the following products:
      | sku       |
      | pineapple |
      | potatoe   |
    And I am logged in as "Mary"

  Scenario: I successfully add a product to pinned items
    Given I am on the "pineapple" product page
    When I pin the current page
    Then I should see the text "Products pineapple | Edit"

  Scenario: I'm successfully redirected to pinned item
    Given I am on the "pineapple" product page
    And I pin the current page
    And I should see the text "Products pineapple | Edit"
    And I am on the "potatoe" product page
    When I click on the pinned item "Products pineapple | Edit"
    And I wait to be on the "pineapple" product page
    Then I should be on the product "pineapple" edit page

  Scenario: I successfully add a product to pinned items1
    Given I am on the "pineapple" product page
    When I pin the current page
    Then I should see the text "Products pineapple | Edit"

  Scenario: I'm successfully redirected to pinned item1
    Given I am on the "pineapple" product page
    And I pin the current page
    And I should see the text "Products pineapple | Edit"
    And I am on the "potatoe" product page
    When I click on the pinned item "Products pineapple | Edit"
    And I wait to be on the "pineapple" product page
    Then I should be on the product "pineapple" edit page

  Scenario: I successfully add a product to pinned items2
    Given I am on the "pineapple" product page
    When I pin the current page
    Then I should see the text "Products pineapple | Edit"

  Scenario: I'm successfully redirected to pinned item2
    Given I am on the "pineapple" product page
    And I pin the current page
    And I should see the text "Products pineapple | Edit"
    And I am on the "potatoe" product page
    When I click on the pinned item "Products pineapple | Edit"
    And I wait to be on the "pineapple" product page
    Then I should be on the product "pineapple" edit page

  Scenario: I successfully add a product to pinned items3
    Given I am on the "pineapple" product page
    When I pin the current page
    Then I should see the text "Products pineapple | Edit"

  Scenario: I'm successfully redirected to pinned item3
    Given I am on the "pineapple" product page
    And I pin the current page
    And I should see the text "Products pineapple | Edit"
    And I am on the "potatoe" product page
    When I click on the pinned item "Products pineapple | Edit"
    And I wait to be on the "pineapple" product page
    Then I should be on the product "pineapple" edit page

  Scenario: I successfully add a product to pinned items4
    Given I am on the "pineapple" product page
    When I pin the current page
    Then I should see the text "Products pineapple | Edit"

  Scenario: I'm successfully redirected to pinned item4
    Given I am on the "pineapple" product page
    And I pin the current page
    And I should see the text "Products pineapple | Edit"
    And I am on the "potatoe" product page
    When I click on the pinned item "Products pineapple | Edit"
    And I wait to be on the "pineapple" product page
    Then I should be on the product "pineapple" edit page

  Scenario: I successfully add a product to pinned items5
    Given I am on the "pineapple" product page
    When I pin the current page
    Then I should see the text "Products pineapple | Edit"

  Scenario: I'm successfully redirected to pinned item5
    Given I am on the "pineapple" product page
    And I pin the current page
    And I should see the text "Products pineapple | Edit"
    And I am on the "potatoe" product page
    When I click on the pinned item "Products pineapple | Edit"
    And I wait to be on the "pineapple" product page
    Then I should be on the product "pineapple" edit page

  Scenario: I successfully add a product to pinned items6
    Given I am on the "pineapple" product page
    When I pin the current page
    Then I should see the text "Products pineapple | Edit"

  Scenario: I'm successfully redirected to pinned item6
    Given I am on the "pineapple" product page
    And I pin the current page
    And I should see the text "Products pineapple | Edit"
    And I am on the "potatoe" product page
    When I click on the pinned item "Products pineapple | Edit"
    And I wait to be on the "pineapple" product page
    Then I should be on the product "pineapple" edit page

  Scenario: I successfully add a product to pinned items7
    Given I am on the "pineapple" product page
    When I pin the current page
    Then I should see the text "Products pineapple | Edit"

  Scenario: I'm successfully redirected to pinned item7
    Given I am on the "pineapple" product page
    And I pin the current page
    And I should see the text "Products pineapple | Edit"
    And I am on the "potatoe" product page
    When I click on the pinned item "Products pineapple | Edit"
    And I wait to be on the "pineapple" product page
    Then I should be on the product "pineapple" edit page

  Scenario: I successfully add a product to pinned items8
    Given I am on the "pineapple" product page
    When I pin the current page
    Then I should see the text "Products pineapple | Edit"

  Scenario: I'm successfully redirected to pinned item8
    Given I am on the "pineapple" product page
    And I pin the current page
    And I should see the text "Products pineapple | Edit"
    And I am on the "potatoe" product page
    When I click on the pinned item "Products pineapple | Edit"
    And I wait to be on the "pineapple" product page
    Then I should be on the product "pineapple" edit page
