// JavaScript Document
	function formatCommas(numString) { 
		// extract decimal and digits to right (if any) 
		var re = /\.\d{1,}/; 
		var frac = (re.test(numString)) ? re.exec(numString) : ""; 
		// divide integer portion into three-digit groups 
		var int = parseInt(numString,10).toString(); 
		re = /(-?\d+)(\d{3})/; 
		while (re.test(int)) { 
			int = int.replace(re, "$1,$2"); 
		} 
		return int + frac; 
	} 
	
	YAHOO.namespace("order.buttons");
	
	var weight;
	var special = "Call Sales";
	
	// constants that can be modified by PHP and will still be tested in the PHP
	var maxweight = 50000;
	var stdShipRequestDays = 30; // Number days in advance of ship date
	var stdMinShipRequestDays = 15; // Minimum number of days that orders can be modified
	var minQtyRequired = 999; // Default minimum quantity for an order item (only care if it is 0 or not)
	
	var onDeleteClick = function() 
	{
		alert ('delete');
		document.OrderForm.cmd = "delete";
	}
	var onDisplayClick = function() 
	{
		document.OrderForm.cmd = "display";
	}
	YAHOO.order.buttons.initButtons = function () 
		{
			// Create Buttons using existing <input> elements as a data source
				
/*
			var oSubmit = new YAHOO.widget.Button("Submit", { onclick: { fn: onButtonClick } });
			var oDelete = new YAHOO.widget.Button("Delete", { onclick: { fn: onButtonClick } });
			var oDisplay = YAHOO.widget.Button("Display", { onclick: { fn: onButtonClick } });
			var oMain = YAHOO.widget.Button("Main", { onclick: { fn: onButtonClick } });
*/
			var oSubmit = new YAHOO.widget.Button("Submit");
			var oMain = new YAHOO.widget.Button("Main", {type:"link",href:"{APP_DIR}/{HOME_APP}" });
			var oDelete = new YAHOO.widget.Button("Delete", {type:"link",href:"{APP_PATH}/{APP_NAME}?cmd=delete&orordn={orordn}" });
			var oDisplay = new YAHOO.widget.Button("Display", {type:"link",href:"{APP_PATH}/{APP_NAME}?cmd=display" });
		} 
		// YAHOO.util.Event.onContentReady("OrderPushButtons", YAHOO.order.buttons.initButtons);
/*
	YAHOO.widget.Button.prototype._submitForm = function() {
		var oForm = this.getForm();
		if(oForm) {
			YAHOO.widget.Button.addHiddenFieldsToForm(oForm);
			this.createHiddenField();
			var listeners = YAHOO.util.Event.getListeners( oForm, 'submit' );
			var submitForm = true;
			for( var j = 0; j < listeners.length; j++ )
			{
				if( listeners[ j ].fn.apply( listeners[ j ].adjust ) == false ) submitForm = false;
			}
			if( submitForm ) oForm.submit();
		}
	};
*/
	function addOrder (e)
	{
		//this function is called when a form is submitted.
		if (typeof(e) == "string") {
			//the id was supplied, get the object reference
			e = xGetElementById(e);
			if (!e) {
				return true;
			}
		}
	
		var elm=e;
		if (!e.nodeName) {
			//was fired by yahoo
			elm = (e.srcElement) ? e.srcElement : e.target;
		}
		if (elm.nodeName.toLowerCase() != 'form') {
			elm = searchUp(elm,'form');
		}
		frm = elm;
		var total = 0;
		if (!frm.elements)
		{
			var oroqtyArray = this.elements["oroqty[]"];
			var ctwghtArray = this.elements["ctwght[]"];
			var minQtyArray = this.elements["MinQty[]"];
			var ordWeight   = this.OrderWeight;
		} else {
			var oroqtyArray = frm.elements["oroqty[]"];
			var ctwghtArray = frm.elements["ctwght[]"];
			var minQtyArray = frm.elements["MinQty[]"];
			var ordWeight   = frm.OrderWeight;
		}

		if (oroqtyArray)
		{
			var valid = true;
			for (i = 0; i < oroqtyArray.length; i++)
			{
				if (oroqtyArray[i].value != "")
				{
					// alert ("Qty: " + oroqtyArray[i].value + " Weight: "+ctwghtArray[i].value);
					if (isNaN (oroqtyArray[i].value) && oroqtyArray[i].value != special)
					{
						alert ("Please enter a valid number - "+oroqtyArray[i].value+" Special: "+special);
						valid = false;
					} else if (!isNaN (oroqtyArray[i].value)) {
						curqty = parseInt (oroqtyArray[i].value); minqty = parseInt (minQtyArray[i].value);
						// alert ("Cur: "+curqty+" Min: "+minqty);
						if (minQtyRequired != 0 && oroqtyArray[i].value != 0 && curqty < minqty)
						{
							addClassName(oroqtyArray[i],'validation-failed');
							alert ("Minimum quantity must be entered.");
							// oroqtyArray[i].focus ();
							valid = false;
						}
						total += oroqtyArray[i].value * ctwghtArray[i].value;
					}
				}
			}
		}
		if (!valid)
		{
			YAHOO.util.Event.stopEvent(this);
		}
		if (total > maxweight) // Max weight for truck
		{
			ordWeight.style.color = "#FF0000";
		} else {
			ordWeight.style.color = "#00CCFF";
		}
		 ordWeight.value = total;
		return valid;
	}
	function checkShipDate ()
	{
		var frm = document.OrderForm;
		var orrqdt = frm.ShipYear.value+'-'+frm.ShipMonth.value+'-'+frm.ShipDay.value;
		var testOrrqdt = frm.ShipMonth.value+'-'+frm.ShipDay.value+'-'+frm.ShipYear.value;
		var cmd = document.OrderForm.cmd.value;
		// alert (orrqdt);
		// do we have a 4 digit year or 2 digit year?
		/*
		var dtArray = orrqdt.split ("/");

		if (orrqdt.length == dtArray.length) dtArray = orrqdt.split ("-");

		if (dtArray [0].length != 4 && dtArray [2].length == 2)
		{
			dtArray [2] = "20" + dtArray [2];
			orrqdt = dtArray [0] + "/" + dtArray [1] + "/" + dtArray [2];
		}
		*/
		var today = new Date ();
		// var day1 = today.getTime ();
		var shipday = new Date (frm.ShipYear.value, frm.ShipMonth.value - 1, frm.ShipDay.value);
		var minutes=1000*60;
		var hours=minutes*60;
		var days=hours*24;
		// var diff = parseInt((d - day1)/days);
		var diff = Math.ceil((shipday.getTime()-today.getTime())/days);

		if (cmd == "add" && diff < stdShipRequestDays)
		{
			alert ("Ship date must be at least " + stdShipRequestDays + "days from today.\n\nIf an emergency shipment is required, please call Art Oksuita at \n(715) 344-9310 x 105 or email Art at art@pointbeer.com.\nThank you.");
			return false;
		} else if (cmd == "modify" && diff < stdMinShipRequestDays)
		{
			// alert ("Days: " + (d - day1)/days);
			alert ("Ship date must be at least " + stdMinShipRequestDays + " days from today (" + diff + " days).");
			return false;
		} else {
			frm.orrqdt.value = orrqdt;
			return true;
		}
	}
	function checkShipWeight ()
	{
		var weight = parseInt (document.getElementById ("OrderWeight").value);

		if (weight > maxweight)
		{
			fmtWeight = formatCommas (maxweight);
			alert ("Order weight is over "+fmtWeight+" lbs");
			return false;
		} else if (weight <= 0.000001 && document.OrderForm.cmd.value != 'modify')
		{
			alert ("You must order at least one item");
			return false;
		} else {
			return true;
		}
	}
	function checkMinQuantities ()
	{
		var oroqtyArray = document.OrderForm.elements["oroqty[]"];
		var minQtyArray = document.OrderForm.elements["MinQty[]"];

		if (oroqtyArray)
		{
			for (i = 0; i < oroqtyArray.length; i++)
			{
				if (oroqtyArray[i].value != "")
				{
					// alert ("Qty: " + frm.oroqty[i].value + " Weight: "+frm.ctwght[i].value);
					if (isNaN (oroqtyArray[i].value) && oroqtyArray[i].value != special)
					{
						alert ("Please enter a valid number");
						addClassName(oroqtyArray[i],'validation-failed');
						return false;
					} else if (!isNaN (oroqtyArray[i].value)) {
						curqty = parseInt (oroqtyArray[i].value); minqty = parseInt (minQtyArray[i].value);
						// alert ("Cur: "+curqty+" Min: "+minqty);
						if (minQtyRequired != 0 && oroqtyArray[i].value != 0 && curqty < minqty)
						//if (oroqtyArray[i].value != 0 && oroqtyArray[i].value < minQtyArray[i].value)
						{
							addClassName(oroqtyArray[i],'validation-failed');
							alert ("Minimum quantity must be entered");
							oroqtyArray[i].focus ();
							return false;
						}
					}
				}
			}
			return true;
		}
		alert ("You must order at least one item");
		return false;
	}

	function checkRequiredComment ()
	{
		var frm = document.OrderForm;
		var cmd = frm.cmd.value;

		if (cmd == "modify" && !frm.OrderComment3.value)
		{
			alert ("A comment is required");
			return false;  // require at least one comment
		} else {
			return true;
		}
	}
	function validateSubmit (e)
	{
		var frm = document.OrderForm;
		
		var evt = (e) ? e : window.event;       //IE reports window.event not arg 
		
		if (!FIC_checkForm (this) || !addOrder (frm) || !checkShipDate () || !checkShipWeight () || !checkMinQuantities ())  // ship weight is before minimum quantities because min qty doesn't correct ship weight
		{
			// YAHOO.util.Event.removeListener(frm, "submit", FIC_checkForm);
			YAHOO.util.Event.stopEvent(e);
			return false;
		}
	}
	function noSubmit (e)
	{
		var evt = (e) ? e : window.event;       //IE reports window.event not arg 
		var unicode=evt.charCode? evt.charCode : evt.keyCode ? evt.keyCode : null;
		if (unicode)
		{
//			alert (unicode);
			if (unicode == 13) // return
			{	
				unicode=9; //return the tab key
				// alert (evt.charCode? evt.charCode : evt.keyCode);
				evt.cancelBubble = true;
				YAHOO.util.Event.stopEvent(e);
				return false;
			}
		}
	}
	function addInvoicePallets (e)
	{
		//this function is called when a form is submitted.
		if (typeof(e) == "string") {
			//the id was supplied, get the object reference
			e = xGetElementById(e);
			if (!e) {
				return true;
			}
		}
	
		var elm=e;
		if (!e.nodeName) {
			//was fired by yahoo
			elm = (e.srcElement) ? e.srcElement : e.target;
		}
		if (elm.nodeName.toLowerCase() != 'form') {
			elm = searchUp(elm,'form');
		}
		frm = elm;

		frm.PalletAmount.value = frm.palqty.value * frm.PalletCost.value;
		return true;
	}
	function attachDOMReady () 
	{
		// YAHOO.util.Event.addFocusListener(window, addOrder, document.OrderForm);
		// YAHOO.util.Event.on(window, 'load', function() 
		// {   
			// Attach event handlers to all of the inputs 
			// YAHOO.util.event.on(document.getElementsByTagName('input'), 'change', addOrder);   addOrder();
		if (document.OrderForm.MaxWeight.value) maxweight = parseInt (document.OrderForm.MaxWeight.value);
		if (document.OrderForm.StdShipRequestDays.value) stdShipRequestDays = parseInt (document.OrderForm.StdShipRequestDays.value);
		if (document.OrderForm.StdMinShipRequestDays.value) stdMinShipRequestDays = parseInt (document.OrderForm.StdMinShipRequestDays.value);
		if (document.OrderForm.MinQtyRequired.value) minQtyRequired = parseInt (document.OrderForm.MinQtyRequired.value);

		if (document.OrderForm)
		{
			YAHOO.util.Event.addListener("OrderForm", "submit", validateSubmit);
			YAHOO.util.Event.addListener("OrderForm", "keydown", noSubmit);
			// YAHOO.util.Event.addListener("OrderForm", "change", addOrder);
			var inputs = document.getElementsByTagName('input');
			for(var i=0;i<inputs.length;i++) {
				// if (inputs [i].name == "oroqty[]") alert (inputs [i].name+": ");
				if (inputs [i].name == "oroqty[]")
					YAHOO.util.Event.addListener(inputs[i], "change", addOrder, document.OrderForm);
			}
			weight = document.getElementById ("OrderWeight");
			weight.style.color = "#00CCFF";
			addOrder(document.OrderForm);
		// }); 
			if (document.OrderForm.cmd.value == "modify")
			{
				// displayComment ('OrderComment');
			}	
			if (document.OrderForm.ReadOnly.value == "Y")
			{
				inputFields = document.OrderForm.getElementsByTagName('input')
		
				for(var i=0;i<inputFields.length;i++)
				{
					inputFields [i].tabindex = -1;
				}
			}
		
			var chkoroqtyArray = document.OrderForm.elements["oroqty[]"];
			if (chkoroqtyArray)  // Highlight order items that have quantities
			{
				var cssid;
				for (i = 0; i < chkoroqtyArray.length; i++)
				{
					if (chkoroqtyArray[i].value != "" && chkoroqtyArray[i].value != special)
					{
						cssid = document.getElementById ("OrderItemRow" + (i + 1)); 
						cssid.style.backgroundColor = "#DEC279";
						cssid.style.color = "#000";
						cssidinput = cssid.getElementsByTagName("input");
						for (j=0; j<cssidinput.length; j++)
						{
							cssidinput [j].style.backgroundColor = "transparent";
							cssidinput [j].style.color = "black";
						}
					}
				}
			}	
		}
		if (document.InvoiceForm)
		{
			YAHOO.util.Event.addListener('InvoiceForm', "change", addInvoicePallets, document.InvoiceForm);
		}
	}
//  YAHOO.util.Event.addListener(window, "load", attachDOMReady);