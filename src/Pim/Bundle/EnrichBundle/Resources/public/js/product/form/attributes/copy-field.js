'use strict';

define([
        'backbone',
        'underscore',
        'text!pim/template/product/tab/attribute/copy-field',
        'pim/i18n'
    ],
    function (Backbone, _, template, i18n) {
        return Backbone.View.extend({
            tagName: 'div',
            locale: null,
            scope: null,
            data: '',
            template: _.template(template),
            selected: false,
            events: {
                'change .copy-field-selector': 'selectionChanged',
                'click label': 'select'
            },
            initialize: function () {
                this.selected = false;

                return this;
            },
            render: function () {
                this.$el.empty();

                var templateContext = {
                    type: this.fieldType,
                    label: this.attribute.label[this.context.locale],
                    data: this.data,
                    config: this.config,
                    context: this.context,
                    attribute: this.attribute,
                    selected: this.selected,
                    locale: this.locale,
                    scope: this.scope,
                    i18n: i18n
                };

                this.$el.html(this.template(templateContext));

                // this.field.getTemplateContext().done(_.bind(function (templateContext) {
                //     this.$('.field-input').html(this.field.renderCopyInput(templateContext, this.locale, this.scope));
                // }, this));

                this.delegateEvents();

                return this;
            },
            setData: function (data) {
                this.data = data;
            },
            setLocale: function (locale) {
                this.locale = locale;
            },
            setScope: function (scope) {
                this.scope = scope;
            },
            selectionChanged: function (event) {
                this.selected = event.currentTarget.checked;
            },
            select: function () {
                this.$('.copy-field-selector').click();
            },
            setSelected: function (selected) {
                this.selected = selected;
            }
        });
    }
);
