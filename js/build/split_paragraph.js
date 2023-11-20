!function(e,t){"object"==typeof exports&&"object"==typeof module?module.exports=t():"function"==typeof define&&define.amd?define([],t):"object"==typeof exports?exports.CKEditor5=t():(e.CKEditor5=e.CKEditor5||{},e.CKEditor5.split_paragraph=t())}(self,(()=>(()=>{var e={"ckeditor5/src/core.js":(e,t,r)=>{e.exports=r("dll-reference CKEditor5.dll")("./src/core.js")},"ckeditor5/src/ui.js":(e,t,r)=>{e.exports=r("dll-reference CKEditor5.dll")("./src/ui.js")},"dll-reference CKEditor5.dll":e=>{"use strict";e.exports=CKEditor5.dll}},t={};function r(o){var a=t[o];if(void 0!==a)return a.exports;var s=t[o]={exports:{}};return e[o](s,s.exports,r),s.exports}r.d=(e,t)=>{for(var o in t)r.o(t,o)&&!r.o(e,o)&&Object.defineProperty(e,o,{enumerable:!0,get:t[o]})},r.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t);var o={};return(()=>{"use strict";r.d(o,{default:()=>i});var e=r("ckeditor5/src/core.js"),t=r("ckeditor5/src/ui.js");class a extends e.Command{execute(){const{model:e,sourceElement:t}=this.editor,[r,o]=e.document.selection.getFirstPosition().path,s=(new DOMParser).parseFromString(this.editor.getData(),"text/html").body.children;if(0===r&&0===o)return;if(0===s.length)return;const i=s[s.length-1].innerText.replace(String.fromCharCode(160),"").length;if(r===s.length-1&&o===i)return;const n=[],l=[],d=e=>e.reduce(((e,t)=>e+t.outerHTML),"");let p=0;Array.from(s).forEach((e=>{if(p<r&&n.push(e),p===r){const[t,r]=a.splitNode(e,o);t&&n.push(t),r&&l.push(r)}p>r&&l.push(e),p+=1}));const c=t.closest(".paragraphs-subform").closest("tr"),h=c.querySelector("[data-paragraphs-split-text-type]").dataset.paragraphsSplitTextType,u=[...c.parentNode.children].filter((e=>e.querySelector(".paragraphs-subform"))).indexOf(c)+1,g=d(n),f=d(l);window._splitParagraph={data:{first:g,second:f},selector:t.dataset.drupalSelector},t.closest(".paragraphs-container").querySelector("input.paragraph-type-add-delta.modal").value=u,t.closest(".paragraphs-container").querySelector(`input[data-paragraph-type="${h}"].field-add-more-submit`).dispatchEvent(new Event("mousedown"))}refresh(){this.isEnabled=!0}static splitNode(e,t){let r=0;const o=e=>{if(e.nodeType===Node.TEXT_NODE){if(e.data.length>t-r){const o=e.data.substring(0,t-r),a=e.data.substring(t-r);return[o?document.createTextNode(o):null,a?document.createTextNode(a):null]}return r+=e.data.length,[e,null]}const a=[],s=[];if(e.childNodes.forEach((e=>{if(0===s.length){const[t,r]=o(e);t&&a.push(t),r&&s.push(r)}else s.push(e)})),0===s.length)return[e,null];const i=e.cloneNode(),n=e.cloneNode();return a.forEach((e=>{i.appendChild(e)})),s.forEach((e=>{n.appendChild(e)})),[i.childNodes.length>0?i:null,n.childNodes.length>0?n:null]};return o(e)}}class s extends e.Plugin{init(){null!=this.editor.sourceElement.closest(".paragraphs-container").querySelector("input.paragraph-type-add-delta.modal")&&(this.editor.ui.componentFactory.add("splitParagraph",(e=>{const r=this.editor.commands.get("splitParagraph"),o=new t.ButtonView(e);return o.set({label:this.editor.t("Simple Split Paragraph"),icon:'<?xml version="1.0" encoding="utf-8"?>\r\n\r\n<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">\r\n\x3c!-- Uploaded to: SVG Repo, www.svgrepo.com, Generator: SVG Repo Mixer Tools --\x3e\r\n<svg width="800px" height="800px" viewBox="0 0 17 17" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">\r\n\t<path d="M10.646 13.146l0.707 0.707-2.853 2.854-2.854-2.854 0.707-0.707 1.647 1.647v-3.772h1v3.772l1.646-1.647zM8 2.207v3.772h1v-3.772l1.646 1.646 0.707-0.707-2.853-2.853-2.854 2.853 0.707 0.707 1.647-1.646zM0 8v1h17v-1h-17z"/>\r\n</svg>',tooltip:!0}),o.bind("isOn","isEnabled").to(r,"value","isEnabled"),this.listenTo(o,"execute",(()=>this.editor.execute("splitParagraph"))),o})),this.editor.commands.add("splitParagraph",new a(this.editor)))}afterInit(){if(window._splitParagraph){if("string"==typeof window._splitParagraph.data.second){const e=this.editor.sourceElement.closest(".paragraphs-subform").closest("tr"),t=e?.previousElementSibling?.previousElementSibling;t&&t.querySelector(`[data-drupal-selector="${window._splitParagraph.selector}"]`)&&setTimeout((()=>{this.editor.setData(window._splitParagraph.data.second),window._splitParagraph.data.second=null}),0)}"string"==typeof window._splitParagraph.data.first&&this.editor.sourceElement.dataset.drupalSelector===window._splitParagraph.selector&&setTimeout((()=>{this.editor.setData(window._splitParagraph.data.first),window._splitParagraph.data.first=null}),0)}}}const i={SplitParagraph:s}})(),o.default})()));