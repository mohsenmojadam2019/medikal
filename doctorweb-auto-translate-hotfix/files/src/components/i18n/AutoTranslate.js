 'use client';

import { useEffect, useRef } from 'react';
import { useLanguage } from '@/lib/context/LanguageContext';
import { translateVisibleText } from './dictionary';

const SKIP_TAGS = new Set([
  'SCRIPT',
  'STYLE',
  'NOSCRIPT',
  'CODE',
  'PRE',
  'SVG',
  'PATH',
]);

const ATTRIBUTES = [
  'placeholder',
  'title',
  'aria-label',
  'alt',
];

function shouldSkip(node) {
  const parent = node.parentElement;
  if (!parent) return true;
  if (SKIP_TAGS.has(parent.tagName)) return true;
  if (parent.closest('[data-no-auto-translate="true"]')) return true;
  if (parent.isContentEditable) return true;
  return false;
}

export default function AutoTranslate() {
  const { locale } = useLanguage();
  const originalsRef = useRef(new WeakMap());
  const attributesRef = useRef(new WeakMap());

  useEffect(() => {
    const originals = originalsRef.current;
    const attributeOriginals = attributesRef.current;
    let frame = null;

    const translateTextNode = (node) => {
      if (shouldSkip(node)) return;

      const current = node.nodeValue || '';
      if (!current.trim()) return;

      if (!originals.has(node)) {
        originals.set(node, current);
      }

      const original = originals.get(node);
      node.nodeValue =
        locale === 'fa'
          ? original
          : translateVisibleText(original, locale);
    };

    const translateElementAttributes = (element) => {
      if (!(element instanceof Element)) return;
      if (element.closest('[data-no-auto-translate="true"]')) return;

      let saved = attributeOriginals.get(element);
      if (!saved) {
        saved = {};
        attributeOriginals.set(element, saved);
      }

      ATTRIBUTES.forEach((attribute) => {
        if (!element.hasAttribute(attribute)) return;

        if (!(attribute in saved)) {
          saved[attribute] = element.getAttribute(attribute);
        }

        const original = saved[attribute];
        element.setAttribute(
          attribute,
          locale === 'fa'
            ? original
            : translateVisibleText(original, locale)
        );
      });
    };

    const translateRoot = (root) => {
      if (!root) return;

      if (root.nodeType === Node.TEXT_NODE) {
        translateTextNode(root);
        return;
      }

      if (root.nodeType !== Node.ELEMENT_NODE &&
          root.nodeType !== Node.DOCUMENT_NODE) {
        return;
      }

      if (root.nodeType === Node.ELEMENT_NODE) {
        translateElementAttributes(root);
      }

      const walker = document.createTreeWalker(
        root,
        NodeFilter.SHOW_TEXT | NodeFilter.SHOW_ELEMENT
      );

      let current = walker.currentNode;
      while (current) {
        if (current.nodeType === Node.TEXT_NODE) {
          translateTextNode(current);
        } else if (current.nodeType === Node.ELEMENT_NODE) {
          translateElementAttributes(current);
        }
        current = walker.nextNode();
      }
    };

    const scheduleTranslate = (root = document.body) => {
      if (frame) cancelAnimationFrame(frame);
      frame = requestAnimationFrame(() => {
        translateRoot(root);
        frame = null;
      });
    };

    document.documentElement.lang = locale;
    document.documentElement.dir = locale === 'en' ? 'ltr' : 'rtl';
    document.body?.setAttribute(
      'dir',
      locale === 'en' ? 'ltr' : 'rtl'
    );

    scheduleTranslate(document.body);

    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.type === 'characterData') {
          scheduleTranslate(mutation.target);
          return;
        }

        mutation.addedNodes.forEach((node) => {
          scheduleTranslate(node);
        });

        if (mutation.type === 'attributes') {
          scheduleTranslate(mutation.target);
        }
      });
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true,
      characterData: true,
      attributes: true,
      attributeFilter: ATTRIBUTES,
    });

    return () => {
      observer.disconnect();
      if (frame) cancelAnimationFrame(frame);
    };
  }, [locale]);

  return null;
}
