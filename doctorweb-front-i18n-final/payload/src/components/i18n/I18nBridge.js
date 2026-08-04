'use client';

import { useEffect, useRef } from 'react';
import { ConfigProvider } from 'antd';
import faIR from 'antd/locale/fa_IR';
import arEG from 'antd/locale/ar_EG';
import enUS from 'antd/locale/en_US';
import dayjs from 'dayjs';
import 'dayjs/locale/fa';
import 'dayjs/locale/ar';
import 'dayjs/locale/en';
import { useLanguage } from '@/lib/context/LanguageContext';

const ANT_LOCALES = { fa: faIR, ar: arEG, en: enUS };
const SKIP_TAGS = new Set([
  'SCRIPT', 'STYLE', 'CODE', 'PRE', 'TEXTAREA',
  'NOSCRIPT', 'SVG', 'PATH',
]);
const ATTRIBUTES = ['placeholder', 'title', 'aria-label', 'alt'];

function elementFor(node) {
  if (!node) return null;
  return node.nodeType === Node.ELEMENT_NODE
    ? node
    : node.parentElement;
}

function shouldSkip(node) {
  const element = elementFor(node);
  if (!element) return true;
  if (SKIP_TAGS.has(element.tagName)) return true;
  return Boolean(element.closest(
    '[data-i18n-skip], .notranslate, [translate="no"], [contenteditable="true"]',
  ));
}

function AutoTranslate() {
  const { locale, revision, translateText } = useLanguage();
  const originalTextRef = useRef(new WeakMap());
  const lastTextRef = useRef(new WeakMap());
  const originalAttributesRef = useRef(new WeakMap());
  const lastAttributesRef = useRef(new WeakMap());

  useEffect(() => {
    if (typeof document === 'undefined') return undefined;

    const originalText = originalTextRef.current;
    const lastText = lastTextRef.current;
    const originalAttributes = originalAttributesRef.current;
    const lastAttributes = lastAttributesRef.current;
    let applying = false;
    let frame = null;

    const translateTextNode = (node) => {
      if (!node?.nodeValue || shouldSkip(node)) return;

      const current = node.nodeValue;
      const previous = lastText.get(node);

      if (!originalText.has(node) || current !== previous) {
        originalText.set(node, current);
      }

      const original = originalText.get(node) ?? current;
      const next = locale === 'fa' ? original : translateText(original);

      if (typeof next === 'string' && next !== current) {
        applying = true;
        node.nodeValue = next;
        applying = false;
      }

      lastText.set(node, next);
    };

    const translateElement = (element) => {
      if (!(element instanceof Element) || shouldSkip(element)) return;

      let originals = originalAttributes.get(element);
      let previousValues = lastAttributes.get(element);

      if (!originals) {
        originals = {};
        originalAttributes.set(element, originals);
      }
      if (!previousValues) {
        previousValues = {};
        lastAttributes.set(element, previousValues);
      }

      for (const attribute of ATTRIBUTES) {
        if (!element.hasAttribute(attribute)) continue;
        const current = element.getAttribute(attribute) || '';

        if (!Object.hasOwn(originals, attribute) ||
            current !== previousValues[attribute]) {
          originals[attribute] = current;
        }

        const original = originals[attribute];
        const next = locale === 'fa' ? original : translateText(original);

        if (next !== current) {
          applying = true;
          element.setAttribute(attribute, next);
          applying = false;
        }
        previousValues[attribute] = next;
      }
    };

    const translateTree = (root) => {
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
        translateElement(root);
      }

      const walker = document.createTreeWalker(
        root,
        NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT,
      );

      let node = walker.currentNode;
      while (node) {
        if (node.nodeType === Node.TEXT_NODE) translateTextNode(node);
        if (node.nodeType === Node.ELEMENT_NODE) translateElement(node);
        node = walker.nextNode();
      }
    };

    const schedule = (root = document.body) => {
      if (frame) cancelAnimationFrame(frame);
      frame = requestAnimationFrame(() => {
        translateTree(root);
        frame = null;
      });
    };

    dayjs.locale(locale);
    schedule(document.body);

    const observer = new MutationObserver((mutations) => {
      if (applying) return;
      for (const mutation of mutations) {
        if (mutation.type === 'characterData') {
          schedule(mutation.target);
          continue;
        }
        for (const node of mutation.addedNodes) schedule(node);
      }
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true,
      characterData: true,
    });

    return () => {
      observer.disconnect();
      if (frame) cancelAnimationFrame(frame);
    };
  }, [locale, revision, translateText]);

  return null;
}

export default function I18nBridge({ children }) {
  const { locale, direction } = useLanguage();

  return (
    <ConfigProvider
      locale={ANT_LOCALES[locale] || faIR}
      direction={direction}
    >
      <AutoTranslate />
      {children}
    </ConfigProvider>
  );
}
