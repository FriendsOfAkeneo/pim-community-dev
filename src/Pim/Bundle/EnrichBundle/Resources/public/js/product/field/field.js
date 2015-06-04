'use strict';

define([
        'jquery',
        'backbone',
        'underscore',
        'text!pim/template/product/field/field',
        'pim/attribute-manager',
        'pim/i18n',
        'oro/mediator'
    ],
    function ($, Backbone, _, fieldTemplate, AttributeManager, i18n, mediator) {
        var FieldModel = Backbone.Model.extend({
            values: []
        });

        return Backbone.View.extend({
            tagName: 'div',
            className: 'field-container',
            options: {},
            attributes: function () {
                return {
                    'data-attribute': this.options ? this.options.code : null
                };
            },
            attribute: null,
            fieldType: 'text',
            context: {},
            model: FieldModel,
            template: _.template(fieldTemplate),
            elements: {},
            editable: true,
            ready: true,
            valid: true,
            initialize: function (attribute) {
                this.attribute = attribute;
                this.model     = new FieldModel({values: []});
                this.elements  = {};
                this.context   = {};

                return this;
            },
            render: function () {
                debugger;
                this.setEditable(true);
                mediator.trigger('field:extension:add', {'field': this});

                this.getTemplateContext().done(_.bind(function (templateContext) {
                    this.$el.empty();
                    this.$el.html(this.template(templateContext));
                    this.$('.form-field .field-input').append(this.renderInput(templateContext));

                    _.each(this.elements, _.bind(function (elements, position) {
                        var $container = this.$('.' + position + '-elements-container');
                        $container.empty();
                        _.each(elements, _.bind(function (element) {
                            if (typeof element.render === 'function') {
                                $container.append(element.render().$el);
                            } else {
                                $container.append(element);
                            }
                        }, this));
                    }, this));
                    this.delegateEvents();
                }, this));

                return this;
            },
            renderInput: function () {
                throw new Error('You should implement your field template');
            },
            renderCopyInput: function (context, locale, scope) {
                context.value = AttributeManager.getValue(
                    this.model.get('values'),
                    this.attribute,
                    locale,
                    scope
                );
                context.editMode = 'disabled';

                return this.renderInput(context);
            },
            getTemplateContext: function () {
                var deferred = $.Deferred();

                deferred.resolve({
                    type: this.fieldType,
                    label: this.attribute.label[this.context.uiLocale] ?
                        this.attribute.label[this.context.uiLocale] :
                        this.attribute.code,
                    value: this.getCurrentValue(),
                    context: this.context,
                    attribute: this.attribute,
                    info: this.elements,
                    editMode: this.getEditMode(),
                    i18n: i18n
                });

                return deferred.promise();
            },
            updateModel: function () {
                this.valid = true;
            },
            setValues: function (values) {
                if (values.length === 0) {
                    values.push(AttributeManager.getValue(
                        [],
                        this.attribute,
                        this.context.locale,
                        this.context.scope
                    ));
                }

                this.model.set('values', values);
            },
            setContext: function (context) {
                this.context = context;
            },
            addElement: function (position, code, element) {
                if (!this.elements[position]) {
                    this.elements[position] = {};
                }
                this.elements[position][code] = element;
            },
            removeElement: function (position, code) {
                if (this.elements[position] && this.elements[position][code]) {
                    delete this.elements[position][code];
                }
            },
            setValid: function (valid) {
                this.valid = valid;
            },
            getValid: function () {
                return this.valid;
            },
            complete: function () {
                return true;
            },
            setFocus: function () {
                this.$('input').first().focus();
            },
            setEditable: function (editable) {
                this.editable = editable;
            },
            getEditable: function () {
                return this.editable;
            },
            setReady: function (ready) {
                this.ready = ready;
            },
            isReady: function () {
                return this.ready;
            },
            getEditMode: function () {
                if (this.editable) {
                    return 'edit';
                } else {
                    return 'view';
                }
            },
            getCurrentValue: function () {
                return AttributeManager.getValue(
                    this.model.get('values'),
                    this.attribute,
                    this.context.locale,
                    this.context.scope
                );
            },
            setCurrentValue: function (value) {
                var productValue = this.getCurrentValue();

                productValue.value = value;
            }
        });
    }
);
