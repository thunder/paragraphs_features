/**
 * @file defines SplitParagraphCommand, which is executed when the splitParagraph
 * toolbar button is pressed.
 */

import { Command } from 'ckeditor5/src/core';

export default class SplitParagraphCommand extends Command {
  execute() {
    const { model, sourceElement } = this.editor;
    const [splitElement, splitText] = model.document.selection.getFirstPosition().path;
    const elements = (new DOMParser()).parseFromString(this.editor.getData(), 'text/html').body.children;

    // Cursor is at the start.
    if (splitElement === 0 && splitText === 0) {
      return;
    }

    // All lines are empty.
    if (elements.length === 0) {
      return;
    }

    // Cursor is at the end.
    const sanitizedInnerTextOfLastElement = elements[elements.length - 1].innerText.replace(String.fromCharCode(160), '').length;
    if (splitElement === elements.length - 1 && splitText === sanitizedInnerTextOfLastElement) {
      return;
    }

    const elementsBefore = [];
    const elementsAfter = [];
    const nodeArrayToHTML = (nodeArray) => nodeArray.reduce((data, element) => data + element.outerHTML, '');

    let i = 0;
    Array.from(elements).forEach((el) => {
      if (i < splitElement) {
        elementsBefore.push(el);
      }

      if (i === splitElement) {
        const [nodeBefore, nodeAfter] = SplitParagraphCommand.splitNode(el, splitText);

        if (nodeBefore) {
          elementsBefore.push(nodeBefore);
        }

        if (nodeAfter) {
          elementsAfter.push(nodeAfter);
        }
      }

      if (i > splitElement) {
        elementsAfter.push(el);
      }
      i += 1;
    });

    // Get paragraph type and position.
    const paragraph = sourceElement.closest('.paragraphs-subform').closest('tr');
    const paragraphType = paragraph.querySelector('[data-paragraphs-split-text-type]').dataset.paragraphsSplitTextType;
    const paragraphDelta = [...paragraph.parentNode.children].filter(el => el.querySelector('.paragraphs-subform')).indexOf(paragraph) + 1;

    // Store the value of the paragraphs.
    const firstData = nodeArrayToHTML(elementsBefore);
    const secondData = nodeArrayToHTML(elementsAfter);
    window._splitParagraph = {
      data: {
        first: firstData,
        second: secondData,
      },
      selector: sourceElement.dataset.drupalSelector,
    };

    // Add new paragragraph after current.
    sourceElement.closest('.paragraphs-container').querySelector('input.paragraph-type-add-delta.modal').value = paragraphDelta;
    sourceElement.closest('.paragraphs-container').querySelector(`input[data-paragraph-type="${paragraphType}"].field-add-more-submit`).dispatchEvent(new Event('mousedown'));
  }

  refresh() {
    this.isEnabled = true;
  }

  static splitNode(node, splitAt) {
    let characterCount = 0;

    const nestedSplitter = (n) => {
      if (n.nodeType === Node.TEXT_NODE) {
        // Split position within text node.
        if (n.data.length > splitAt - characterCount) {
          const textBeforeSplit = n.data.substring(0, splitAt - characterCount);
          const textAfterSplit = n.data.substring(splitAt - characterCount);

          return [
            textBeforeSplit ? document.createTextNode(textBeforeSplit) : null,
            textAfterSplit ? document.createTextNode(textAfterSplit) : null,
          ];
        }

        characterCount += n.data.length;
        return [n, null];
      }

      const childNodesBefore = [];
      const childNodesAfter = [];
      n.childNodes.forEach((childNode) => {
        // Split not yet reached.
        if (childNodesAfter.length === 0) {
          const [childNodeBefore, childNodeAfter] = nestedSplitter(childNode);

          if (childNodeBefore) {
            childNodesBefore.push(childNodeBefore);
          }

          if (childNodeAfter) {
            childNodesAfter.push(childNodeAfter);
          }
        } else {
          childNodesAfter.push(childNode);
        }
      });

      // Node was not split.
      if (childNodesAfter.length === 0) {
        return [n, null];
      }

      const nodeBefore = n.cloneNode();
      const nodeAfter = n.cloneNode();

      childNodesBefore.forEach((childNode) => {
        nodeBefore.appendChild(childNode);
      });

      childNodesAfter.forEach((childNode) => {
        nodeAfter.appendChild(childNode);
      });

      return [
        nodeBefore.childNodes.length > 0 ? nodeBefore : null,
        nodeAfter.childNodes.length > 0 ? nodeAfter : null,
      ];
    };

    return nestedSplitter(node);
  }
}
