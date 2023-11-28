!function(t,e){"object"==typeof exports&&"object"==typeof module?module.exports=e():"function"==typeof define&&define.amd?define([],e):"object"==typeof exports?exports.CKEditor5=e():(t.CKEditor5=t.CKEditor5||{},t.CKEditor5.split_paragraph=e())}(self,(()=>(()=>{var t={"ckeditor5/src/core.js":(t,e,r)=>{t.exports=r("dll-reference CKEditor5.dll")("./src/core.js")},"ckeditor5/src/ui.js":(t,e,r)=>{t.exports=r("dll-reference CKEditor5.dll")("./src/ui.js")},"dll-reference CKEditor5.dll":t=>{"use strict";t.exports=CKEditor5.dll}},e={};function r(o){var a=e[o];if(void 0!==a)return a.exports;var s=e[o]={exports:{}};return t[o](s,s.exports,r),s.exports}r.d=(t,e)=>{for(var o in e)r.o(e,o)&&!r.o(t,o)&&Object.defineProperty(t,o,{enumerable:!0,get:e[o]})},r.o=(t,e)=>Object.prototype.hasOwnProperty.call(t,e);var o={};return(()=>{"use strict";r.d(o,{default:()=>i});var t=r("ckeditor5/src/core.js"),e=r("ckeditor5/src/ui.js");class a extends t.Command{execute(){const{model:t,sourceElement:e}=this.editor,r=Drupal.t("[Splitting in progress... ⌛]"),o=this.editor.getData();t.change((e=>{e.insertText(r,t.document.selection.getFirstPosition())}));const s=(new DOMParser).parseFromString(this.editor.getData(),"text/html").body,[i,n,d]=a.splitNode(s,r);if(!d||!i||!n)return void this.editor.setData(o);const l=e.closest(".paragraphs-subform").closest("tr"),p=l.querySelector("[data-paragraphs-split-text-type]").dataset.paragraphsSplitTextType,c=[...l.parentNode.children].filter((t=>t.querySelector(".paragraphs-actions"))).indexOf(l)+1;window._splitParagraph={data:{first:i.outerHTML,second:n.outerHTML},selector:e.dataset.drupalSelector},e.closest(".paragraphs-container").querySelector("input.paragraph-type-add-delta.modal").value=c,e.closest(".paragraphs-container").querySelector(`input[data-paragraph-type="${p}"].field-add-more-submit`).dispatchEvent(new Event("mousedown"))}refresh(){this.isEnabled=!0}static splitNode(t,e){const r=t=>{if(t.nodeType===Node.TEXT_NODE){const r=t.data.indexOf(e);if(r>=0){const o=t.data.substring(0,r),a=t.data.substring(r+e.length);return[o?document.createTextNode(o):null,a?document.createTextNode(a):null,!0]}return[t,null,!1]}const o=[],a=[];let s=!1;if(t.childNodes.forEach((t=>{if(s)a.push(t);else{const[e,i,n]=r(t);s=n,e&&o.push(e),i&&a.push(i)}})),!s)return[t,null,!1];const i=t.cloneNode(),n=t.cloneNode();return o.forEach((t=>{i.appendChild(t)})),a.forEach((t=>{n.appendChild(t)})),[i.childNodes.length>0?i:null,n.childNodes.length>0?n:null,s]};return r(t)}}class s extends t.Plugin{init(){null!=this.editor.sourceElement.closest(".paragraphs-container")?.querySelector("input.paragraph-type-add-delta.modal")&&(this.editor.ui.componentFactory.add("splitParagraph",(t=>{const r=this.editor.commands.get("splitParagraph"),o=new e.ButtonView(t);return o.set({label:this.editor.t("Simple Split Paragraph"),icon:'<?xml version="1.0" encoding="utf-8"?>\r\n\r\n<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">\r\n\x3c!-- Uploaded to: SVG Repo, www.svgrepo.com, Generator: SVG Repo Mixer Tools --\x3e\r\n<svg width="800px" height="800px" viewBox="0 0 17 17" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">\r\n\t<path d="M10.646 13.146l0.707 0.707-2.853 2.854-2.854-2.854 0.707-0.707 1.647 1.647v-3.772h1v3.772l1.646-1.647zM8 2.207v3.772h1v-3.772l1.646 1.646 0.707-0.707-2.853-2.853-2.854 2.853 0.707 0.707 1.647-1.646zM0 8v1h17v-1h-17z"/>\r\n</svg>',tooltip:!0}),o.bind("isOn","isEnabled").to(r,"value","isEnabled"),this.listenTo(o,"execute",(()=>this.editor.execute("splitParagraph"))),o})),this.editor.commands.add("splitParagraph",new a(this.editor)))}afterInit(){if(window._splitParagraph){if("string"==typeof window._splitParagraph.data.second){const t=this.editor.sourceElement.closest(".paragraphs-subform").closest("tr"),e=t?.previousElementSibling?.previousElementSibling;e&&e.querySelector(`[data-drupal-selector="${window._splitParagraph.selector}"]`)&&setTimeout((()=>{this.editor.setData(window._splitParagraph.data.second),window._splitParagraph.data.second=null}),0)}"string"==typeof window._splitParagraph.data.first&&this.editor.sourceElement.dataset.drupalSelector===window._splitParagraph.selector&&setTimeout((()=>{this.editor.setData(window._splitParagraph.data.first),window._splitParagraph.data.first=null}),0)}}}const i={SplitParagraph:s}})(),o.default})()));