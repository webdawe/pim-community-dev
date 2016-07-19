'use strict';

define([
    'jquery',
    'underscore',
    'oro/translator',
    'routing',
    'pim/filter/filter',
    'pim/fetcher-registry',
    'pim/user-context',
    'pim/i18n',
    'pim/initselect2',
    'text!pim/template/filter/attribute/multiselect-reference-data',
    'jquery.select2'
], function (
    $,
    _,
    __,
    Routing,
    BaseFilter,
    FetcherRegistry,
    UserContext,
    i18n,
    initSelect2,
    template
) {
    return BaseFilter.extend({
        shortname: 'multi-ref-data',
        template: _.template(template),
        attribute: null,
        choicePromise: null,
        events: {
            'change [name="filter-value"]': 'updateState',
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
            //return _.isEmpty(this.getValue());
            return false;
        },

        postRender: function () {
            this.getChoiceUrl().then(function (choiceUrl) {
                var options = {
                    ajax: {
                        url: choiceUrl,
                        cache: true,
                        data: function (term) {
                            return {
                                search: term,
                                options: {
                                    locale: UserContext.get('catalogLocale')
                                }
                            };
                        },
                        results: function (data) {
                            return data;
                        }
                    },
                    initSelection: function (element, callback) {
                        if (null === this.choicePromise) {
                            this.choicePromise = $.get(choiceUrl);
                        }

                        this.choicePromise.then(function (response) {
                            var results = response.results;
                            var choices = _.map($(element).val().split(','), function (choice) {
                                return _.findWhere(results, {id: choice});
                            });
                            callback(choices);
                        });
                    }.bind(this),
                    multiple: true
                };

                if ('EMPTY' !== this.getOperator()) {
                    this.$('input.select2').select2(options);
                }
            }.bind(this));
        },

        /**
         * {@inherit}
         */
        renderInput: function () {
            if (_.isUndefined(this.getOperator())) {
                this.setOperator(this.config.operators[0]);
            }

            return this.template({
                __: __,
                value: this.getValue(),
                field: this.getField(),
                operator: this.getOperator(),
                isEditable: this.isEditable(),
                operatorChoices: this.config.operators
            });
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
                }
            }.bind(this));
        },

        /**
         * {@inherit}
         */
        updateState: function () {
            //var operator = this.$('[name="filter-operator"]').val();
            var cleanedValues = [];

            //this.setOperator(operator);

            if ('EMPTY' !== this.getOperator()) {
                var value = this.$('[name="filter-value"]').val().split(/[\s,]+/);
                cleanedValues = _.reject(value, function (val) {
                    return '' === val;
                });
            }

            this.setData({
                field: this.getField(),
                operator: this.getOperator(),
                value: cleanedValues
            });
        },

        selectOperator: function (e) {
            e.preventDefault();

            var operator = $(e.target).data('value');
            this.setOperator(operator);
            this.updateState();
        },

        getChoiceUrl: function () {
            return FetcherRegistry.getFetcher('reference-data-configuration').fetchAll()
                .then(function (config) {
                    return Routing.generate(
                        'pim_ui_ajaxentity_list',
                        {
                            'class': config[this.attribute.reference_data_name].class,
                            'dataLocale': UserContext.get('uiLocale'),
                            'collectionId': this.attribute.id,
                            'options': {'type': 'code'}
                        }
                    );
                }.bind(this));
        }
    });
});
