(()=>{var a={n:t=>{var e=t&&t.__esModule?()=>t.default:()=>t;return a.d(e,{a:e}),e},d:(t,e)=>{for(var n in e)a.o(e,n)&&!a.o(t,n)&&Object.defineProperty(t,n,{enumerable:!0,get:e[n]})},o:(a,t)=>Object.prototype.hasOwnProperty.call(a,t),r:a=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(a,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(a,"__esModule",{value:!0})}},t={};(()=>{"use strict";a.r(t);const e=flarum.core.compat["admin/app"];var n=a.n(e);function o(a,t){return o=Object.setPrototypeOf||function(a,t){return a.__proto__=t,a},o(a,t)}const r=flarum.core.compat["admin/components/ExtensionPage"];var i=a.n(r);const s=flarum.core.compat["common/components/Switch"];var l=a.n(s);const c=[{domain:"com",name:"United States",central:"https://affiliate-program.amazon.com/"},{domain:"co.uk",name:"United Kingdom",central:"https://affiliate-program.amazon.co.uk/"},{domain:"de",name:"Deutschland",central:"https://partnernet.amazon.de/"},{domain:"fr",name:"France",central:"https://partenaires.amazon.fr/"},{domain:"co.jp",name:"Japan",central:"https://affiliate.amazon.co.jp/"},{domain:"ca",name:"Canada",central:"https://associates.amazon.ca/"},{domain:"cn",name:"China",central:"https://associates.amazon.cn/"},{domain:"it",name:"Italia",central:"https://programma-affiliazione.amazon.it/"},{domain:"es",name:"España",central:"https://afiliados.amazon.es/"},{domain:"in",name:"India",central:"https://affiliate-program.amazon.in/"},{domain:"com.br",name:"Brazil",central:"https://associados.amazon.com.br/"},{domain:"com.mx",name:"Mexico",central:"https://afiliados.amazon.com.mx/"},{domain:"com.au",name:"Australia",central:"https://affiliate-program.amazon.com.au/"}];var p="fof-amazon-affiliation.",d="fof-amazon-affiliation.admin.settings.",f=function(a){var t,e;function r(){return a.apply(this,arguments)||this}e=a,(t=r).prototype=Object.create(e.prototype),t.prototype.constructor=t,o(t,e);var i=r.prototype;return i.oninit=function(t){a.prototype.oninit.call(this,t)},i.content=function(){var a=this;return[m(".container",[m(".Form-group",[l().component({state:this.setting(p+"keep-existing-tag")()>0,onchange:this.setting(p+"keep-existing-tag")},n().translator.trans(d+"field.keep-existing-tag")),m(".helpText",n().translator.trans(d+"field.keep-existing-tag-help")),m(".helpText",n().translator.trans(d+"field.help-mediaembed"))]),m(".Form-group",[l().component({state:this.setting(p+"remove-tag-if-unhandled")()>0,onchange:this.setting(p+"remove-tag-if-unhandled")},n().translator.trans(d+"field.remove-tag-if-unhandled")),m(".helpText",n().translator.trans(d+"field.remove-tag-if-unhandled-help")),m(".helpText",n().translator.trans(d+"field.help-mediaembed"))]),m("h2",n().translator.trans(d+"title.tags")),m(".helpText",n().translator.trans(d+"title.tags-mediaembed")),c.map((function(t){return m(".Form-group",[m("label",n().translator.trans(d+"field.tag",t)),m("input[type=text].FormControl",{bidi:a.setting(p+"affiliate-tag."+t.domain),placeholder:n().translator.trans(d+"field.tag-placeholder")})])})),this.submitButton()])]},r}(i());n().initializers.add("fof/amazon-affiliation",(function(){n().extensionData.for("fof-amazon-affiliation").registerPage(f)}))})(),module.exports=t})();
//# sourceMappingURL=admin.js.map