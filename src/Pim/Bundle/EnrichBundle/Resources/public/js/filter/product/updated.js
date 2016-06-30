/* global console */
'use strict';

define([
        'underscore',
        'oro/translator',
        'pim/filter/filter',
        'routing',
        'text!pim/template/filter/product/updated',
        'pim/fetcher-registry',
        'pim/user-context',
        'pim/i18n',
        'jquery.select2',
        'datepicker'
    ], function (_, __, BaseFilter, Routing, template, fetcherRegistry, userContext, i18n, initSelect2, Datepicker) {
    return BaseFilter.extend({
        template: _.template(template),
        events: {
            'change [name="filter-operator"], [name="filter-value"]': 'updateState'
        },
        initialize: function (config) {
            this.config = config.config;

            return BaseFilter.prototype.initialize.apply(this, arguments);
        },
        renderInput: function () {
            if (undefined === this.getOperator()) {
                this.setOperator(_.first(_.values(this.config.operators)));
            }

            return this.template({
                __: __,
                field: this.getField(),
                operator: this.getOperator(),
                value: this.getValue(),
                operatorChoices: this.config.operators
            });
        },
        postRender: function () {
            this.$('[name="filter-operator"]').select2();

            if ('>=' === this.getOperator()) {
                Datepicker
                    .init(this.$('[name="filter-value"]').parent())
                    .on('changeDate', this.updateState.bind(this));
            }
        },
        updateState: function () {
            var oldOperator = this.getFormData().operator;

            var value    = this.$('[name="filter-value"]').val();
            var operator = this.$('[name="filter-operator"]').val();

            if ('ALL' === operator) {
                this.clearData();

                return;
            }

            if (operator !== oldOperator) {
                value = null;
            }

            this.setData({
                field: this.getField(),
                operator: operator,
                value: value
            });
        }
    });
});