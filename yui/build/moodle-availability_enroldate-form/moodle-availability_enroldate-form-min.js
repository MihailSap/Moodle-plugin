YUI.add("moodle-availability_enroldate-form",(function(e,a){M.availability_enroldate=M.availability_enroldate||{},M.availability_enroldate.form=e.Object(M.core_availability.plugin),M.availability_enroldate.form.timeFields=null,M.availability_enroldate.form.startField=null,M.availability_enroldate.form.isSection=null,M.availability_enroldate.form.warningStrings=null,M.availability_enroldate.form.initInner=function(e,a,l){this.timeFields=e,this.startField=a,this.isSection=l},M.availability_enroldate.form.getNode=function(a){var l,i='<span class="availability-relativedate">';i+='<label><select name="relativenumber">';for(var t=1;t<60;t++)i+='<option value="'+t+'">'+t+"</option>";for(i+="</select></label> ",i+='<label><select name="relativednw">',t=0;t<this.timeFields.length;t++)i+='<option value="'+(l=this.timeFields[t]).field+'">'+l.display+"</option>";i+="</select></label> ",i+='<span class="relativestart">'+this.startField+"</span>",i+="</span>";var n=e.Node.create("<span>"+i+"</span>");(t=1,void 0!==a.n&&(t=a.n),n.one("select[name=relativenumber]").set("value",t),t=2,void 0!==a.d&&(t=a.d),n.one("select[name=relativednw]").set("value",t),M.availability_enroldate.form.addedEvents)||(M.availability_enroldate.form.addedEvents=!0,e.one(".availability-field").delegate("change",(function(){M.core_availability.form.update()}),".availability_relativedate select"));return n},M.availability_enroldate.form.fillValue=function(e,a){e.n=Number(a.one("select[name=relativenumber]").get("value")),e.d=Number(a.one("select[name=relativednw]").get("value"))},M.availability_enroldate.form.fillErrors=function(e,a){this.fillValue({},a)}}),"@VERSION@",{requires:["base","node","event","moodle-core_availability-form"]});
