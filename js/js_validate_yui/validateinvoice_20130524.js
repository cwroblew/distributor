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
	
	function toThousands(number) {
		var number = number.toString(), 
		dollars = number.split('.')[0], 
		cents = (number.split('.')[1] || '') +'00';
		dollars = dollars.split('').reverse().join('')
			.replace(/(\d{3}(?!$))/g, '$1,')
			.split('').reverse().join('');
		return dollars + '.' + cents.slice(0, 2);
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
		
		var totwgt = 0;
		
		if (!frm.elements)
		{
			var origtotamt  = this.OrigTotalPrice; // Keep original total
			var asctotamt   = this.TotalPrice      // Updated with Pallet costs
			var ascpalamt   = this.PalletAmount;
			var palqty      = this.palqty;
			var palcost     = this.PalletCost;
			var asctotamt   = this.TotalPrice
		} else {
			var origtotamt  = frm.OrigTotalPrice; // Keep original total
			var asctotamt   = frm.TotalPrice      // Updated with Pallet costs
			var ascpalamt   = frm.PalletAmount;
			var palqty      = frm.palqty;
			var palcost     = frm.PalletCost;
		}
		var totamt = parseFloat (origtotamt.value.replace(/\,/g,''));
		var palamt = palqty.value * palcost.value;
		totamt += palamt;
		ascpalamt.value = palamt.toFixed(2);
		asctotamt.value = toThousands(totamt);
		var emailInvoice = document.getElementById("EmailInvoice");
		emailInvoice.href += "&palqty="+palqty.value;
		return true;
	}
	function attachDOMReady () 
	{
		if (document.InvoiceForm)
		{
			YAHOO.util.Event.addListener('InvoiceForm', "change", addInvoicePallets, document.InvoiceForm);
		}
	}
//  YAHOO.util.Event.addListener(window, "load", attachDOMReady);