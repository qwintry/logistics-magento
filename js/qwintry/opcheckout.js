var PickupPoint = Class.create();
PickupPoint.prototype = {
    initialize: function (form, saveUrl) {
        this.form = form;
        if ($(this.form)) {
            $(this.form).observe('submit', function (event) {
                this.save();
                Event.stop(event);
            }.bind(this));
        }
        this.saveUrl = saveUrl;
        this.onComplete = this.resetLoadWaiting.bindAsEventListener(this);
    },

    updateStore: function () {
        if (checkout.loadWaiting != false) return;
        checkout.setLoadWaiting('shipping-method');
        var request = new Ajax.Request(
            this.saveUrl,
            {
                method: 'post',
                onComplete: this.onComplete,
                onSuccess: this.onUpdateStore,
                onFailure: checkout.ajaxFailure.bind(checkout),
                parameters: Form.serialize(this.form)
            }
        );
    },

    onUpdateStore: function (transport) {
        if (transport && transport.responseText) {
            try {
                response = eval('(' + transport.responseText + ')');
            }
            catch (e) {
                response = {};
            }
        }

        if (response.error) {
            alert(response.message);
            return false;
        }

        if (response.update_section) {
            $('checkout-' + response.update_section.name + '-load').update(response.update_section.html);
        }

    },

    resetLoadWaiting: function (transport) {
        checkout.setLoadWaiting(false);
    },
}