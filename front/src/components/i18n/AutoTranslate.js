'use client';

import { useEffect } from 'react';
import { useLanguage } from '@/lib/context/LanguageContext';
import { translateVisibleText } from './dictionary';

const skipTags = new Set([
  'SCRIPT', 'STYLE', 'NOSCRIPT', 'CODE', 'PRE', 'SVG', 'PATH'
]);

const textOriginal = new WeakMap();
const attributeOriginal = new WeakMap();
const attributes = ['placeholder', 'title', 'aria-label', 'alt'];

function translateNode(root, locale) {
  if (!root || typeof Node === 'undefined') return;

  const translateText = (node) => {
    const parent = node.parentElement;
    if (!parent || skipTags.has(parent.tagName)) return;
    if (parent.closest('[data-no-auto-translate="true"]')) return;
    if (!node.nodeValue?.trim()) return;

    if (!textOriginal.has(node)) {
      textOriginal.set(node, node.nodeValue);
    }

    const source = textOriginal.get(node);
    node.nodeValue =
      locale === 'fa' ? source : translateVisibleText(source, locale);
  };

  const translateAttributes = (element) => {
    if (!(element instanceof Element)) return;
    if (element.closest('[data-no-auto-translate="true"]')) return;

    let originals = attributeOriginal.get(element);
    if (!originals) {
      originals = {};
      attributeOriginal.set(element, originals);
    }

    attributes.forEach((name) => {
      if (!element.hasAttribute(name)) return;
      if (!(name in originals)) {
        originals[name] = element.getAttribute(name) || '';
      }
      const source = originals[name];
      element.setAttribute(
        name,
        locale === 'fa' ? source : translateVisibleText(source, locale)
      );
    });
  };

  if (root.nodeType === Node.TEXT_NODE) {
    translateText(root);
    return;
  }

  const walker = document.createTreeWalker(
    root,
    NodeFilter.SHOW_TEXT | NodeFilter.SHOW_ELEMENT
  );

  let current = walker.currentNode;
  while (current) {
    if (current.nodeType === Node.TEXT_NODE) translateText(current);
    if (current.nodeType === Node.ELEMENT_NODE) translateAttributes(current);
    current = walker.nextNode();
  }
}

export default function AutoTranslate() {
  const { locale } = useLanguage();

  useEffect(() => {
    document.documentElement.lang = locale;
    document.documentElement.dir = locale === 'en' ? 'ltr' : 'rtl';
    document.body?.setAttribute('dir', locale === 'en' ? 'ltr' : 'rtl');

    let frame = requestAnimationFrame(() => {
      translateNode(document.body, locale);
    });

    const observer = new MutationObserver((mutations) => {
      cancelAnimationFrame(frame);
      frame = requestAnimationFrame(() => {
        for (const mutation of mutations) {
          if (mutation.type === 'characterData') {
            translateNode(mutation.target, locale);
          } else {
            mutation.addedNodes.forEach((node) => translateNode(node, locale));
          }
        }
      });
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true,
      characterData: true,
    });

    return () => {
      observer.disconnect();
      cancelAnimationFrame(frame);
    };
  }, [locale]);

  return null;
}
