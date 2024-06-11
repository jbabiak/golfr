import "./widget.scss";
import Choices from "choices.js";

((Drupal, once) => {
  /**
   * Stores the search timeout.
   */
  let timeout;

  class ChoicesAutocomplete {
    /**
     * ChoicesAutocomplete constructor.
     *
     * @param {HTMLSelectElement} element
     *   The select element.
     * @param {Object} settings
     *   The configuration options.
     */
    constructor(element, settings) {
      this.element = element;
      this.settings = settings;
      this.url = element.dataset.autocompletePath;
      this.cache = {};
      this.value = null;

      // Prepare the options.
      settings.plugin.loadingText = Drupal.theme(
        "ajaxProgressThrobber",
        settings.plugin.loadingText
      );
      settings.plugin.noChoicesText = settings.plugin.noResultsText;
      if (settings.instance.allowed_characters) {
        settings.instance.allowed_characters =
          settings.instance.allowed_characters.toLowerCase().split("");
      }

      const instance = new Choices(element, {
        ...settings.plugin,
        shouldSort: false,
        allowHTML: true,
        removeItemButton: true,
        classNames: {
          containerOuter: "choices choices--autocomplete",
          item: "choices__item choices__item--autocomplete",
        },
        callbackOnCreateTemplates() {
          return {
            item: (...args) => {
              const item = Choices.defaults.templates.item.call(this, ...args);
              const button = item.querySelector("button");
              button.innerText = settings.instance.remove_item_text;
              button.setAttribute(
                "aria-label",
                `${settings.instance.remove_item_text}: ${args[1].value}`
              );
              return item;
            },
          };
        },
      });
      this.instance = instance;
      this.input = instance.input.element;
      this.container = this.instance.containerOuter.element;

      instance.containerOuter.element.id = element.id;
      element.removeAttribute("id");
      if (this.hasValues()) {
        instance.containerOuter.element.classList.add("is-filled");
      }
      if (element.classList.contains("error")) {
        instance.containerOuter.element.classList.add("has-error");
      }

      element.addEventListener("search", this.onSearch.bind(this));
      element.addEventListener("addItem", this.onAddItem.bind(this));
      element.addEventListener("removeItem", this.onRemoveItem.bind(this));
      element.addEventListener("change", this.onChange.bind(this));
      element.addEventListener("showDropdown", this.onShowDropdown.bind(this));

      this.input.addEventListener("paste", this.onPaste.bind(this));
      this.input.addEventListener("keydown", this.onKeydown.bind(this));
      this.input.addEventListener("keyup", this.onKeyup.bind(this));

      // Replace existing selections with formatted values.
      if (settings.values) {
        const { choices } = instance.config;
        Object.keys(settings.values).forEach((value) => {
          choices.forEach((choice) => {
            const matches = choice.value.match(/\(([\w_]+)\)$/);
            if (choice.value === value || (matches && matches[1] === value)) {
              choice.label = settings.values[value];
              choice.selected = true;
            }
          });
        });
        instance.removeActiveItems();
        instance.setChoices(choices, "value", "label", true);
      }
    }

    /**
     * Set dropdown notice message.
     *
     * @param {string} message
     *   The message.
     */
    setNotice(message) {
      this.instance.choiceList.clear();
      this.instance.choiceList.append(
        this.instance._getTemplate("notice", message)
      );
    }

    /**
     * Check if values exist.
     *
     * @return {true|false}
     *   Returns TRUE or FALSE.
     */
    hasValues() {
      const values = this.instance.getValue();
      return (
        (Array.isArray(values) && values.length) ||
        (!Array.isArray(values) && values)
      );
    }

    /**
     * Check for maximum selected items.
     *
     * @return {boolean}
     *   Returns TRUE on maximum selections. Otherwise, FALSE.
     */
    isMax() {
      return (
        this.instance.config.maxItemCount > 1 &&
        this.instance.getValue(true).length >= this.instance.config.maxItemCount
      );
    }

    /**
     * Add or remove loading state.
     *
     * @param {boolean} loading
     *   Boolean indicating to add or remove loading state.
     */
    setState(loading) {
      if (loading) {
        this.instance.containerOuter.addLoadingState();
      } else {
        this.instance.containerOuter.removeLoadingState();
      }
    }

    /**
     * Handle search event.
     *
     * @param {CustomEvent} event
     *   The event.
     */
    onSearch(event) {
      this.container.classList.add("is-searching");

      // Ajax result fetcher.
      const { value } = event.detail;
      if (
        (this.url && !this.settings.instance.minlength) ||
        value.length >= this.settings.instance.minlength
      ) {
        if (this.value !== event.detail.value) {
          this.setNotice(this.settings.plugin.loadingText);
        }
        if (timeout) {
          clearTimeout(timeout);
        }
        timeout = setTimeout(this.fetch.bind(this, event.detail.value), 500);
      }
    }

    /**
     * Handle add item event.
     */
    onAddItem() {
      if (this.hasValues()) {
        this.container.classList.add("is-filled");
      }
    }

    /**
     * Handle remove item event.
     */
    onRemoveItem() {
      const blank = document.createElement("option");
      this.element.appendChild(blank);
    }

    /**
     * Handle change event.
     */
    onChange() {
      if (this.hasValues()) {
        this.container.classList.add("is-filled");
      } else {
        this.container.classList.remove("is-filled");
      }
      if (!this.input.value) {
        this.container.classList.remove("is-searching");
      }
    }

    /**
     * Handle show dropdown event.
     */
    onShowDropdown() {
      if (this.isMax()) {
        this.setNotice(this.settings.plugin.maxItemText);
      }
    }

    /**
     * Handle paste event.
     *
     * @param {ClipboardEvent} event
     *   The event.
     */
    onPaste(event) {
      if (!this.settings.instance.input_type) {
        return;
      }
      let value = event.clipboardData
        .getData("text")
        .split("")
        .filter((key) => {
          // Prevent non-allowed characters.
          return this.isAllowed(key);
        });

      // Prevent input past maximum number of characters.
      if (
        this.settings.instance.maxlength &&
        value.length > this.settings.instance.maxlength
      ) {
        value = value.splice(0, this.settings.instance.maxlength);
      }

      event.target.value = value.join("");
      event.preventDefault();
    }

    /**
     * Handle keydown event.
     *
     * @param {KeyboardEvent} event
     *   The event.
     */
    onKeydown(event) {
      if (event.metaKey || event.key.length !== 1) {
        return;
      }
      const { key } = event;
      const { value } = event.target;

      // Prevent input past maximum number of characters.
      if (
        this.settings.instance.maxlength &&
        value.length >= this.settings.instance.maxlength
      ) {
        event.preventDefault();
      }

      // Prevent non-allowed characters.
      if (this.settings.instance.input_type && !this.isAllowed(key)) {
        event.preventDefault();
      }
    }

    /**
     * Check if character is allowed.
     *
     * @param {String} key
     *   The input key.
     *
     * @return {boolean}
     *   Returns true or false.
     */
    isAllowed(key) {
      let passed = this.settings.instance.allowed_characters.includes(
        key.toLowerCase()
      );
      if (!passed) {
        switch (this.settings.instance.input_type) {
          case "alpha":
            passed = key.match(/[\p{Letter}\p{Mark}]/u) && !key.match(/\d/);
            break;

          case "numeric":
            passed = key.match(/\d/);
            break;

          default:
            passed = key.match(/[\p{Letter}\p{Mark}]/u);
            break;
        }
      }
      return passed;
    }

    /**
     * Handle keyup event.
     */
    onKeyup() {
      if (this.input.value) {
        this.container.classList.add("is-searching");
      } else {
        this.container.classList.remove("is-searching");
      }
    }

    /**
     * Fetch search results.
     *
     * @param {String} input
     *   The search input.
     */
    fetch(input) {
      this.value = input;

      if (this.cache[input]) {
        this.populate([...this.cache[input]], input);
      }
      // Populate from Ajax request when static cache is missing.
      else {
        this.setState(true);
        const url = new URL(this.url, window.location.origin);
        url.searchParams.append("q", input);
        fetch(url.toString(), {
          headers: { "Content-Type": "application/json; charset=utf-8" },
        })
          .then((response) => response.json())
          .then((response) => {
            // Store response in static cache.
            this.cache[input] = response;
            if (this.value === input) {
              this.populate([...response], input);
            }
          })
          .catch(() => {
            alert(Drupal.t("An error occurred. Please try again."));
          });
      }
    }

    /**
     * Populate dropdown with search results.
     *
     * @param {Array} items
     *   An array of search results to populate.
     * @param {String} input
     *   The search input.
     */
    populate(items, input) {
      this.setState(false);
      let values = this.instance.getValue(true);
      if (!Array.isArray(values)) {
        values = typeof values !== "undefined" ? [values] : [];
      }

      // Prepare and normalize item values.
      items.forEach((item) => {
        item.value = item.value
          // Remove quote encapsulation.
          .replace(/^"(.*)"$/, "$1")
          // Remove double quotes added by the tags encoder.
          .replace(/"{2}/g, '"')
          .trim();
        item.id = item.value.replace(/^.+\s\((\d+)\)/, "$1");
        item.match = item.value.replace(/^(.*)\s\(\d+\)$/, "$1").toLowerCase();
      });

      // Prevent duplicates by removing current selections from results.
      const ids = [];
      const labels = values.map((value) => {
        const matches = value.match(/\s\(\d+\)"?$/);
        if (matches) {
          ids.push(matches[0].replace(/\D/g, "").trim());
        }
        return value
          .replace(/^"(.*)"$/, "$1")
          .trim()
          .toLowerCase();
      });
      items = items.filter((item) => {
        labels.push(item.match);
        const matches = item.value.match(/\((\d+)\)$/);
        if (matches) {
          return ids.indexOf(matches[1]) === -1;
        }
        return true;
      });

      // Allow selection of newly auto-created items.
      if (
        this.settings.instance.auto_create &&
        labels.indexOf(input.toLowerCase().trim()) === -1
      ) {
        items.unshift({
          value: input.trim(),
          label: input.trim(),
        });
      }

      if (!items.length) {
        this.setNotice(this.settings.plugin.noResultsText);
      } else {
        items.push(
          this.instance.config.choices.find((choice) => choice.placeholder)
        );
        this.instance.setChoices(items, "value", "label", true);
      }
    }
  }

  /**
   * Attach Choices.js to entity reference fields.
   */
  Drupal.behaviors.choicesAutocomplete = {
    attach: function attach(context, settings) {
      once(
        "choices-autocomplete",
        "select.choices-autocomplete",
        context
      ).forEach((element) => {
        const options = settings.choices_autocomplete[element.id];
        if (!options) {
          return;
        }
        element.choicesAutocomplete = new ChoicesAutocomplete(element, options);
      });
    },
  };
})(Drupal, once);
