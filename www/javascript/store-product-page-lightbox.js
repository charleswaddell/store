/**
 * Displays an alert if there are no quantities entered on the items table
 * on a product page
 *
 * @param integer the product id.
 * @param Array item_ids the array of item ids displayed on this product page.
 * @param integer the category the product belongs to.
 */
function StoreProductPageLightBox(product_id, item_ids, source_category)
{
	this.item_ids = item_ids;
	this.product_id = product_id;
	this.source_category = source_category;
	this.button_values = [];

	StoreProductPageLightBox.superclass.constructor.call(this);
}

StoreProductPageLightBox.xml_rpc_client = new XML_RPC_Client('xml-rpc/cart');
StoreProductPageLightBox.submit_message = 'Updating Cart…';
StoreProductPageLightBox.loading_message = 'Loading…';
StoreProductPageLightBox.close_text = 'Close';
StoreProductPageLightBox.add_button_id = 'add_button';
StoreProductPageLightBox.empty_message = '<h2>All Items Removed</h2>' +
	'You no longer have any items from this page in your cart.';

YAHOO.lang.extend(StoreProductPageLightBox, StoreProductPage, {
// {{{ init: function()

init: function()
{
	StoreProductPageLightBox.superclass.init.call(this);

	this.mini_cart_entry_count = 0;
	this.mini_cart = document.getElementById('store_product_cart');

	var cart_links = YAHOO.util.Dom.getElementsByClassName(
		'product-page-cart-link');

	if (cart_links.length > 0) {
		YAHOO.util.Event.on(cart_links, 'click', this.loadMiniCart, this, true);
	}

	YAHOO.util.Event.on(window, 'scroll', this.positionMiniCart, this, true);

	// make any click on the body close the mini-cart, except for the
	// mini-cart itself
	YAHOO.util.Event.on(this.mini_cart, 'click',
		function(e) {
			YAHOO.util.Event.stopPropagation(e);
	});

	YAHOO.util.Event.on(document.body, 'click', this.bodyCloseMiniCart, this, true);
},

// }}}
// {{{ handleFormSubmit: function()

handleFormSubmit: function(e)
{
	YAHOO.util.Event.preventDefault(e);

	if (!this.hasQuantity()) {
		this.openMiniCart('<h3>' +
			StoreProductPage.enter_quantity_message + '</h3>');

	} else {
		this.changeButtonText(e);
		var entries = [];

		for (var i = 0; i < this.item_ids.length; i++) {
			var entry = this.getEntry(this.item_ids[i]);

			if (entry !== null) {
				entries.push(entry);
			}
		}

		this.addEntriesToCart(entries);
		this.openMiniCart(StoreProductPageLightBox.loading_message);
	}
}

// }}}
});

// {{{ StoreProductPageLightBox.prototype.hasQuantity

StoreProductPageLightBox.prototype.hasQuantity = function()
{
	var has_quantity = false;

	// check if any quantity box has a value
	for (var i = 0; i < this.quantity_boxes.length; i++) {
		if (this.quantity_boxes[i].value != 0) {
			has_quantity = true;
			break;
		}
	}

	return (this.quantity_boxes.length == 0 || has_quantity);
}

// }}}
// {{{ StoreProductPageLightBox.prototype.changeButtonText

StoreProductPageLightBox.prototype.changeButtonText = function(e)
{
	var button = document.getElementById(StoreProductPageLightBox.add_button_id);
	this.saveButtonValue(button);
	button.value = StoreProductPageLightBox.submit_message;
}

// }}}
// {{{ StoreProductPageLightBox.prototype.getEntry

StoreProductPageLightBox.prototype.getEntry = function(item_id)
{
	var quantity = document.getElementById(
		'quantity_quantity_renderer_' + item_id).value;

	if (parseInt(quantity) > 0) {
		var entry = {};
		entry.item_id = item_id;
		entry.quantity = quantity;
	} else {
		var entry = null;
	}

	return entry;
}

// }}}
// {{{ StoreProductPageLightBox.prototype.addEntriesToCart

StoreProductPageLightBox.prototype.addEntriesToCart = function(entries)
{
	var that = this;
	function callBack(response)
	{
		// TODO
		// update layout cart icon/info
		//that.setMiniCartContentWithAnimation(response.mini_cart);
		that.setMiniCartContent(response.mini_cart);

		//that.displayCartData(response);
		that.resetForm();
		that.mini_cart_entry_count = response.product_items;
	}

	StoreProductPageLightBox.xml_rpc_client.callProcedure(
		'addEntries', callBack,
		[entries, this.source_category, true],
		['array', 'int', 'boolean']);

	this.openMiniCart('<h3>' + StoreProductPageLightBox.submit_message + '</h3>');
}

// }}}
// {{{ StoreProductPageLightBox.prototype.initMiniCart

StoreProductPageLightBox.prototype.initMiniCart = function()
{
}

// }}}
// {{{ StoreProductPageLightBox.prototype.openMiniCart

StoreProductPageLightBox.prototype.openMiniCart = function(contents)
{
	this.setMiniCartContent(contents);

	YAHOO.util.Dom.setStyle(this.mini_cart, 'opacity', 0);
	this.mini_cart.style.display = 'block';
	this.positionMiniCart();

	var cart_animation = new YAHOO.util.Anim(
		this.mini_cart,
		{ opacity: { from: 0, to: 1 }},
		0.3);

	cart_animation.animate();
}

// }}}
// {{{ StoreProductPageLightBox.prototype.loadMiniCart

StoreProductPageLightBox.prototype.loadMiniCart = function(e)
{
	YAHOO.util.Event.stopPropagation(e);
	YAHOO.util.Event.preventDefault(e);

	var that = this;
	function callBack(response)
	{
		//that.setMiniCartContentWithAnimation(response);
		that.setMiniCartContent(response);
	}

	StoreProductPageLightBox.xml_rpc_client.callProcedure(
		'getMiniCart', callBack, [this.product_id], ['int']);

	this.openMiniCart('<h3>' + StoreProductPageLightBox.loading_message + '</h3>');
}

// }}}
// {{{ StoreProductPageLightBox.prototype.setMiniCartContent

StoreProductPageLightBox.prototype.setMiniCartContent = function(contents)
{
	this.positionMiniCart();

	var content = document.getElementById('store_product_cart_content');
	content.innerHTML = contents;

	// activate any 'remove' buttons
	var remove_buttons = YAHOO.util.Dom.getElementsByClassName(
		'store-remove', 'input', content);

	for (var i = 0; i < remove_buttons.length; i++) {
		YAHOO.util.Event.on(remove_buttons[i], 'click',
			this.removeEntry, this, true);
	}

	// activate any 'close' links
	var close_buttons = YAHOO.util.Dom.getElementsByClassName(
		'store-close-cart', 'a', this.mini_cart);

	for (var i = 0; i < close_buttons.length; i++) {
		YAHOO.util.Event.on(close_buttons[i], 'click',
			this.closeMiniCart, this, true);
	}
}

// }}}
// {{{ StoreProductPageLightBox.prototype.setMiniCartContentWithAnimation

StoreProductPageLightBox.prototype.setMiniCartContentWithAnimation =
	function(contents)
{
	var old_height = this.mini_cart_contents.offsetHeight;
	var new_height = this.getContentHeight(contents);

	var content_animation = new YAHOO.util.Anim(
		this.mini_cart_contents,
		{ height: { to: new_height }},
		0.3);

	// to avoid content that overflows the div, change the content of the
	// div before the content_animation of the content is shorter, otherwise, set it
	// after the content_animation.

	var that = this;
	content_animation.onComplete.subscribe(function() {
		if (old_height < new_height) {
			that.setMiniCartContent(contents);
		}
	});

	content_animation.animate();

	var container_height = (this.mini_cart.offsetHeight -
		this.mini_cart_contents.offsetHeight + new_height)

	var container_top = (YAHOO.util.Dom.getViewportHeight() -
		container_height) / 2;

	var container_animation = new YAHOO.util.Anim(
		this.mini_cart,
		{ top: { to: container_top }},
		0.3);

	container_animation.animate();

	if (old_height >= new_height) {
		this.setMiniCartContent(contents);
	}
}

// }}}
// {{{ StoreProductPageLightBox.prototype.getContentHeight

StoreProductPageLightBox.prototype.getContentHeight = function(contents)
{
	var hidden_div = document.createElement('div');
	hidden_div.style.visiblility = 'hidden';
	hidden_div.height = 0;

	var hidden_content_div = document.createElement('div');
	hidden_content_div.innerHTML = contents;
	hidden_div.appendChild(hidden_content_div);
	this.mini_cart_contents.parentNode.appendChild(hidden_div);

	var new_height = hidden_content_div.offsetHeight;
	this.mini_cart_contents.parentNode.removeChild(hidden_div);

	// if contents are taller than the window, restrict height to
	// the available space
	var new_container_height = (this.mini_cart.offsetHeight -
		this.mini_cart_contents.offsetHeight + new_height);

	if (new_container_height > YAHOO.util.Dom.getViewportHeight()) {
		new_height = (YAHOO.util.Dom.getViewportHeight() -
			(this.mini_cart.offsetHeight -
			(this.mini_cart_contents.offsetHeight)));
	}

	return new_height;
}

// }}}
// {{{ StoreProductPageLightBox.prototype.getContainerTop

StoreProductPageLightBox.prototype.getContainerTop = function(contents)
{
	var content_height = this.getContentHeight(contents);

	var container_height = (this.mini_cart.offsetHeight -
		this.mini_cart_contents.offsetHeight + content_height)

	return Math.max(((YAHOO.util.Dom.getViewportHeight() -
		container_height) / 2), 0);
}

// }}}
// {{{ StoreProductPageLightBox.prototype.removeEntry

StoreProductPageLightBox.prototype.removeEntry = function(e)
{
	YAHOO.util.Event.preventDefault(e);

	var button = YAHOO.util.Event.getTarget(e);
	var parts = button.id.split('_');
	var entry_id = parts[parts.length - 1];

	var that = this;
	function callBack(response)
	{
		// update layout cart icon/display
	}

	StoreProductPageLightBox.xml_rpc_client.callProcedure(
		'removeEntry', callBack,
		[entry_id],
		['int']);

	this.mini_cart_entry_count--;

	if (this.mini_cart_entry_count <= 0) {
		//this.setMiniCartContentWithAnimation(StoreProductPageLightBox.empty_message);
		this.setMiniCartContent(StoreProductPageLightBox.empty_message);
	} else {
		var tr = this.getParentNode(button, 'tr');
		this.removeRow(tr, button);
	}
}

// }}}
// {{{ StoreProductPageLightBox.prototype.removeRow

StoreProductPageLightBox.prototype.removeRow = function(tr, button)
{
	var rows = tr.parentNode.childNodes;
	var index = null;

	for (var i = 0; i < rows.length; i++) {
		var remove_buttons = YAHOO.util.Dom.getElementsByClassName(
			'store-remove', 'input', rows[i]);

		if (remove_buttons.length > 0 && remove_buttons[0].id == button.id) {
			var index = i;
			break;
		}
	}

	if (index !== null) {
		var animation = new YAHOO.util.Anim(
			tr,
			{ opacity: { to: 0 }},
			0.3);

		animation.onComplete.subscribe(function() {
			tr.parentNode.deleteRow(index);
		});

		animation.animate();
	}
}

// }}}
// {{{ StoreProductPageLightBox.prototype.resetForm

StoreProductPageLightBox.prototype.resetForm = function()
{
	this.restoreButtonValue(
		document.getElementById(StoreProductPageLightBox.add_button_id));

	// reset quantites
	for (var i = 0; i < this.quantity_boxes.length; i++) {
		this.quantity_boxes[i].value = 0;
	}
}

// }}}
// {{{ StoreProductPageLightBox.prototype.closeMiniCart

StoreProductPageLightBox.prototype.closeMiniCart = function(e)
{
	if (e) {
		YAHOO.util.Event.preventDefault(e);
	}

	if (this.mini_cart.style.display != 'none') {
		var animation = new YAHOO.util.Anim(
			this.mini_cart,
			{ opacity: { to: 0 }},
			0.3);

		var that = this;
		animation.onComplete.subscribe(function() {
			that.mini_cart.style.display = 'none';
		});

		animation.animate();
	}
}

// }}}
// {{{ StoreProductPageLightBox.prototype.bodyCloseMiniCart

StoreProductPageLightBox.prototype.bodyCloseMiniCart = function(e)
{
	this.closeMiniCart();
}

// }}}
// {{{ StoreProductPageLightBox.prototype.getParentNode

StoreProductPageLightBox.prototype.getParentNode = function(node, tag)
{
	if (node.tagName == tag.toUpperCase()) {
		return node;
	} else {
		return this.getParentNode(node.parentNode, tag);
	}
}

// }}}
// {{{ StoreProductPageLightBox.prototype.saveButtonValue

StoreProductPageLightBox.prototype.saveButtonValue = function(button)
{
	var value = {
		id: button.id,
		value: button.value
	}

	this.button_values.push(value);
}

// }}}
// {{{ StoreProductPageLightBox.prototype.restoreButtonValue

StoreProductPageLightBox.prototype.restoreButtonValue = function(button)
{
	for (var i = 0; i < this.button_values.length; i++) {
		if (this.button_values[i].id == button.id) {
			button.value = this.button_values[i].value;
			break;
		}
	}
}

// }}}
// {{{ StoreProductPageLightBox.prototype.positionMiniCart

StoreProductPageLightBox.prototype.positionMiniCart = function()
{
	// TODO maybe grab the -12 from the css and the 20 from the cart-div region
	var scroll_top = Math.max(-1 * (YAHOO.util.Dom.getDocumentScrollTop() - 20), -12);
	this.mini_cart.style.top = scroll_top + 'px';
}

// }}}