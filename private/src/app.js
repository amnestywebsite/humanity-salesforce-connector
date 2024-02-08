/* global AISC */
const { isFunction } = lodash;

const { apiFetch } = wp;
const { applyFilters } = wp.hooks;

let globalChangeEvent;

window.AISC.cache = window.AISC.cache || {};
const { cache } = window.AISC;

/**
 * Fetch a URL, cache the response, and trigger a callback
 *
 * @param {string} path the path to fetch
 * @param {Function} callback the callback to execute
 *
 * @returns {void}
 */
const cachedFetch = (options = {}) => {
  const key = options.path || options.url;

  if (cache[key]) {
    return Promise.resolve(cache[key]);
  }

  return apiFetch(options)
    .then((resp) => {
      cache[key] = resp;
      return resp;
    })
    .catch(() => {});
};

/**
 * Fetch a Salesforce Object
 *
 * @param {string} object the sObject to fetch
 * @param {Function} callback the callback to execute
 *
 * @returns {void}
 */
const fetchSObject = async (object, callback) => {
  const url = `${AISC.baseurl}sobjects/${object}`;
  await cachedFetch({ url }).then(callback);
};

/**
 * Fetch a Salesforce Object Field
 *
 * @param {string} object the sObject to fetch
 * @param {string} field the sObject Field to fetch
 * @param {Function} callback the callback to execute
 *
 * @returns {Promise}
 */
const fetchSobjectField = (object, field, callback) => {
  const url = `${AISC.baseurl}sobjects/${object}/${field}`;
  cachedFetch({ url }).then(callback);
};

/**
 * Find a parent element, optionally by selector
 *
 * @param {Node} $node the origin node
 * @param {string} selector selector to optionally traverse for
 *
 * @return {Node|null} the found parent
 */
const parent = ($node, selector = '') => {
  let elem = $node.parentElement;

  if (!selector) {
    return elem;
  }

  while (elem != null) {
    if (elem.matches(selector)) {
      return elem;
    }

    elem = elem.parentElement;
  }

  return null;
};

/**
 * Toggle a node's visibility
 *
 * @param {Node} $node the node to manipulate
 * @param {boolean} show the visibility status
 *
 * @returns {void}
 */
const display = ($node, show = true) => {
  if (show) {
    $node.classList.remove('is-hidden');
  } else {
    $node.classList.add('is-hidden');
  }
};

/**
 * Toggle group visibility
 *
 * @param {Event} event the current event
 * @param {array} $groups the container's node groups
 *
 * @return {void}
 */
const handleCustomiseChange = (event, $form) => {
  const { value } = event.target;
  const $groups = Array.from($form.querySelectorAll('[data-fieldtype="group"]'));

  $groups.forEach((g) => {
    display(g, value === 'yes');
  });
};

/**
 * Retrieve data from API and set <select> options
 *
 * @param {Event} event the current event
 *
 * @returns {void}
 */
const handleSObjectChange = (event) => {
  const { name, value: sobject } = event.target;
  const $wrap = parent(event.target, '.cmb-type-group');
  const $inputs = $wrap.querySelectorAll(`select[data-populate="sobject"]:not([name="${name}"])`);

  fetchSObject(sobject, (resp) => {
    let html = '';
    Object.keys(resp).forEach((k) => {
      html += `<option value="${k}">${resp[k]}</option>`;
    });

    $inputs.forEach(($input) => {
      html = html.replace(
        `<option value="${$input.value}">`,
        `<option value="${$input.value}" selected>`,
      );

      // eslint-disable-next-line
      $input.innerHTML = html;
    });
  });
};

/**
 * Handle visibility of signatory count inputs
 *
 * @param {Event} event the current event
 *
 * @returns {void}
 */
const handleQueryMethodChange = (event) => {
  const { value } = event.target;
  const $wrap = parent(event.target, '.cmb-type-group');
  const $inputs = Array.from($wrap.querySelectorAll(`[data-show-on]`));
  const $sobject = $wrap.querySelector('[name*="sobject"]');

  $inputs.forEach(($input) => {
    const type = $input.dataset.showOn;
    const $container = parent($input, '.cmb-row');
    display($container, type === value);

    if (type !== 'field' || !$sobject.value) {
      return;
    }

    $sobject.dispatchEvent(new Event('change', { bubbles: true }));
  });
};

/**
 * Filters for use in {handleFieldValueChange}
 */
const filterCallbacks = {
  text: (response, inputs) => inputs.forEach((input) => display(parent(input, '.cmb-row'))),
  select: (response, inputs) => {
    let html = '';
    Object.keys(response.list).forEach((k) => {
      html += `<option value="${k}">${response.list[k]}</option>`;
    });

    inputs.forEach(($input) => {
      // eslint-disable-next-line
      $input.innerHTML = html;
      display(parent($input, '.cmb-row'));
    });
  },
};

/**
 * Toggle a field's companion field visibility
 *
 * @param {Event} event the current event
 * @param {Node} $form the container node
 */
const handleFieldValueChange = (event) => {
  const { name, value: sfield } = event.target;
  const hasVal = sfield && sfield !== '~';
  const $wrap = parent(event.target, '.cmb-type-group');
  const sobject = $wrap.querySelector('select[name*="sobject"]').value;
  const fname = name.replace(/^.*\[([^\]]+)\]$/, '$1');
  const selector = `[data-show-on="${fname}"]`;
  const $inputs = Array.from($wrap.querySelectorAll(selector));

  $inputs.forEach(($input) => {
    display(parent($input, '.cmb-row'), false);
  });

  if (!hasVal) {
    return;
  }

  fetchSobjectField(sobject, sfield, (resp) => {
    const filtered = $inputs.filter(($i) => $i.dataset.showIf.indexOf(resp.type) !== -1);
    const hasFilter = Object.prototype.hasOwnProperty.call(filterCallbacks, resp.type);

    if (!filtered || !hasFilter) {
      return;
    }

    filterCallbacks[resp.type](resp, filtered);
  });
};

/**
 * Handle all change events on the form
 *
 * @param {Event} event the current event
 * @param {Node} $form the form element
 * @param {array} $groups field group elements
 *
 * @returns {Event}
 */
const handleChangeEvents = (event, $form) => {
  const { target } = event;
  const name = target.name.replace(/^.*\[([^\]]+)\]$/, '$1');
  let cb;

  $form.removeEventListener('change', globalChangeEvent);

  switch (name) {
    case 'customise':
      cb = applyFilters('aisc.events.change.customise', handleCustomiseChange, event, $form);
      cb(event, $form);
      break;

    case 'sobject':
      cb = applyFilters('aisc.events.change.sobject', handleSObjectChange, event, $form);
      cb(event, $form);
      break;

    case 'method':
      cb = applyFilters('aisc.events.change.method', handleQueryMethodChange, event, $form);
      cb(event, $form);
      break;

    case 'field_newsletter':
    case 'field_type':
    case 'field_status':
      cb = applyFilters('aisc.events.change.field', handleFieldValueChange, event, $form);
      cb(event, $form);
      break;

    default:
      cb = applyFilters('aisc.events.change.default', null, event, $form);
      if (isFunction(cb)) {
        cb(event, $form);
      }
      break;
  }

  $form.addEventListener('change', globalChangeEvent);
};

document.addEventListener('DOMContentLoaded', () => {
  const $form = document.querySelector('form.cmb-form[id*="amnesty"][id*="salesforce"]');

  if (!$form) {
    return;
  }

  globalChangeEvent = (e) => handleChangeEvents(e, $form);
  $form.addEventListener('change', globalChangeEvent);
});
