 'use strict';

define(
    [
        'underscore',
        'pim/form',
        'oro/mediator',
        'text!pim/template/product/meta/updated'
    ],
    function (_, BaseForm, mediator, formTemplate) {
        var FormView = BaseForm.extend({
            tagName: 'span',
            template: _.template(formTemplate),
            configure: function () {
                mediator.on('product:action:post_update', _.bind(this.render, this));

                return BaseForm.prototype.configure.apply(this, arguments);
            },
            render: function () {
                this.$el.html(
                    this.template({
                        product: this.getData()
                    })
                );

                return this;
            }
        });

        return FormView;
    }
);
