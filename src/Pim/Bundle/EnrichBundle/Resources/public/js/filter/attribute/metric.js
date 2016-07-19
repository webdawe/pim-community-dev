'use strict';

define([
    'underscore',
    'oro/translator',
    'pim/filter/filter',
    'pim/fetcher-registry',
    'pim/user-context',
    'pim/i18n',
    'text!pim/template/filter/attribute/metric',
    'jquery.select2'
], function (
    _,
    __,
    BaseFilter,
    FetcherRegistry,
    UserContext,
    i18n,
    template
) {
    return BaseFilter.extend({
        shortname: 'metric',
        template: _.template(template),
        events: {
            'change [name="filter-value"], [name="filter-operator"], select.unit': 'updateState'
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
            return _.isEmpty(undefined === this.getValue() || '' === this.getValue().value);
        },

        /**
         * {@inherit}
         */
        renderInput: function (templateContext) {
            if (undefined === this.getValue()) {
                this.setValue({
                    data: '',
                    unit: templateContext.defaultMetricUnit
                })
            }

            return this.template(_.extend({}, templateContext, {
                __: __,
                value: this.getValue(),
                field: this.getField(),
                operator: this.getOperator(),
                operators: this.config.operators,
                isEditable: this.isEditable(),
            }));
        },

        /**
         * Initializes select2 after rendering.
         */
        postRender: function () {
            this.$('select.select2').select2();
        },

        /**
         * {@inherit}
         */
        getTemplateContext: function () {
            return $.when(
                FetcherRegistry.getFetcher('attribute').fetch(this.getField()),
                FetcherRegistry.getFetcher('measure').fetchAll()
            ).then(function (attribute, measures) {
                return {
                    label: i18n.getLabel(attribute.labels, UserContext.get('uiLocale'), attribute.code),
                    units: measures[attribute['metric_family']],
                    defaultMetricUnit: attribute['default_metric_unit'],
                    removable: true
                };
            });
        },

        /**
         * {@inherit}
         */
        updateState: function () {
            var value    = {
                data: this.$('[name="filter-value"]').val(),
                unit: this.$('select.unit').val()
            };
            var operator = this.$('[name="filter-operator"]').val();

            this.setData({
                field: this.getField(),
                operator: operator,
                value: value
            });
        }
    });
});
