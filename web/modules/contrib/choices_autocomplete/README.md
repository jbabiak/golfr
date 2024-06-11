# Choices.js Autocomplete

Provides [Choices.js](https://choices-js.github.io/Choices/) form widget for select lists and entity reference
autocomplete field types. Rich text (HTML+CSS) formatting of entity reference values and autocomplete
results. Supports both standard and views selection entity reference handlers including autocreate (i.g. tags).
Easy configuration with the field widget settings form.

## Field Types

- List (text)
- List (integer)
- List (float)
- Entity Reference

## Themes

Styles for these Drupal themes included:

- Olivero
- Claro

## Element

Need to use Choices.js autocomplete on a custom element? Sure!

```php
$form['custom_element'] = [
  '#type' => 'choices_autocomplete',
  '#title' => t('My select'),
  '#options' => [
    'no' => t('No'),
    'yes' => t('Yes'),
  ],
  '#multiple' => TRUE,
];
```

## Usage

1. Download and install the `drupal/choices_autocomplete` module. Recommended install method is composer:
   ```
   composer require drupal/choices_autocomplete
   ```
2. Go to the "Manage form display" tab of the entity type.
3. Select the "Choices.js Autocomplete" widget on the field.
4. Configure and save changes.
