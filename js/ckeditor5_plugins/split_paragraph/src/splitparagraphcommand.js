/**
 * @file defines SplitParagraphCommand, which is executed when the splitParagraph
 * toolbar button is pressed.
 */

import { Command } from 'ckeditor5/src/core';

export default class SplitParagraphCommand extends Command {
  execute() {
    const { model, sourceElement } = this.editor;
    // @todo Use an html node with display:none instead
    const splitMarker = Drupal.t('[Splitting in progress... ⌛]');
    const originalText = this.editor.getData();

    model.change(writer => {
      writer.insertText(splitMarker, model.document.selection.getFirstPosition());
    });

    const rootElement = (new DOMParser()).parseFromString(this.editor.getData(), 'text/html').body;
    const [elementBefore, elementAfter, markerFound] = SplitParagraphCommand.splitNode(rootElement, splitMarker);

    if (!markerFound || !elementBefore || !elementAfter) {
      this.editor.setData(originalText);
      return;
    }

    // Get paragraph type and position.
    const paragraph = sourceElement.closest('.paragraphs-subform').closest('tr');
    const paragraphType = paragraph.querySelector('[data-paragraphs-split-text-type]').dataset.paragraphsSplitTextType;
    const paragraphDelta = [...paragraph.parentNode.children].filter(el => el.querySelector('.paragraphs-actions')).indexOf(paragraph) + 1;

    // Store the value of the paragraphs.
    window._splitParagraph = {
      data: {
        first: elementBefore.outerHTML,
        second: elementAfter.outerHTML,
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

  static splitNode(node, splitMarker) {
    const nestedSplitter = (n) => {
      if (n.nodeType === Node.TEXT_NODE) {
        // Split position within text node.
        const markerPos = n.data.indexOf(splitMarker);
        if (markerPos >= 0) {
          const textBeforeSplit = n.data.substring(0, markerPos);
          const textAfterSplit = n.data.substring(markerPos + splitMarker.length);

          return [
            textBeforeSplit ? document.createTextNode(textBeforeSplit) : null,
            textAfterSplit ? document.createTextNode(textAfterSplit) : null,
            true,
          ];
        }

        return [n, null, false];
      }

      const childNodesBefore = [];
      const childNodesAfter = [];
      let found = false;
      n.childNodes.forEach((childNode) => {
        // Split not yet reached.
        if (!found) {
          const [childNodeBefore, childNodeAfter, markerFound] = nestedSplitter(childNode);
          found = markerFound;

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
      if (!found) {
        return [n, null, false];
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
        found,
      ];
    };

    return nestedSplitter(node);
  }
}
