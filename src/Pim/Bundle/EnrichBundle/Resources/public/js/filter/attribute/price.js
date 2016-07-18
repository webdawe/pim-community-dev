'use strict';

define([
    'jquery',
    'underscore',
    'oro/translator',
    'pim/filter/filter',
    'pim/fetcher-registry',
    'pim/user-context',
    'pim/i18n',
    'text!pim/template/filter/attribute/price',
    'jquery.select2'
], function (
    $,
    _,
    __,
    BaseFilter,
    FetcherRegistry,
    UserContext,
    i18n,
    template
) {
    return BaseFilter.extend({
        shortname: 'price',
        template: _.template(template),
        events: {
            'change [name="filter-value-data"]': 'updateState',
            'change [name="filter-value-currency"]': 'updateState',
            'change [name="filter-operator"]': 'updateState',
            'click .operator_choice': 'selectOperator'
        },

        /**
         * {@inherit}
         */
        initialize: function (config) {
            this.config = config.config;

            return BaseFilter.prototype.initialize.apply(this, arguments);
        },

        /**
         * {@inherit}
         */
        isEmpty: function () {
            var value = this.getValue();

            return _.isEmpty(value) || _.isEmpty(value.data);
        },

        /**
         * {@inherit}
         */
        renderInput: function () {
            if (_.isUndefined(this.getOperator())) {
                this.setOperator(this.config.operators[0]);
            }

            FetcherRegistry.getFetcher('currency')
                .fetchAll()
                .then(function (currencies) {
                    return this.template({
                        __: __,
                        value: this.getValue(),
                        field: this.getField(),
                        operator: this.getOperator(),
                        isEditable: this.isEditable(),
                        operatorChoices: this.config.operators,
                        currencies: currencies,
                        removable: true
                    });
                }.bind(this)
            );
        },

        /**
         * {@inherit}
         */
        getTemplateContext: function () {
            var fetchAttributePromise = FetcherRegistry.getFetcher('attribute').fetch(this.getField());

            return fetchAttributePromise.then(function (attribute) {
                this.attribute = attribute;

                return {
                    label: i18n.getLabel(attribute.labels, UserContext.get('uiLocale'), attribute.code),
                    removable: true
                };
            }.bind(this));
        },

        /**
         * {@inherit}
         */
        updateState: function () {
            var value = {data: '', currency: ''};

            if ('EMPTY' !== this.getOperator()) {
                value.data = this.$('[name="filter-value-data"]').val();
                value.currency = this.$('[name="filter-value-currency"]').val();
            }

            this.setData({
                field: this.getField(),
                operator: this.getOperator(),
                value: value
            });
        },

        selectOperator: function (e) {
            e.preventDefault();

            var operator = $(e.target).data('value');
            this.setOperator(operator);
            this.updateState();
        }
    });
});
