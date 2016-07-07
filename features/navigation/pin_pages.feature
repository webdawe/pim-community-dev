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
