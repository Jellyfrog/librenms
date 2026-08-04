# Adding new config settings

It is now much easier to let users update a new config option through the WebUI,
for general options. This document shows you how
to add a new config option, and also a new section, to the WebUI.

Config settings are defined in `resources/definitions/config_definitions.json`

Think about the name of your config setting.
For example: a good setting for the snmp community is `snmp.community`.
The dot notation is a path. When the system hydrates the config, it converts the path to a nested array.
If the user replaces the option in config.php, the format is `$config['snmp']['community']`

## Translation

The config definition system supports translation. You must add the English names in the
`resoures/lang/en/settings.php` file (and other languages if you can).

To update the javascript translation files, run:

    ./lnms translation:generate

## Definition Format

For snmp.community, this is the definition:

```json
"snmp.community": {
    "group": "poller",
    "section": "snmp",
    "order": 2,
    "type": "array",
    "default": [
        "public"
    ]
}
```

## Fields

All fields are optional. To show the setting in the web ui, group and section are mandatory, and order is recommended.

* `type`: Sets the type. There are some predefined types. You can also
define custom types and implement them in a vue.js component
* `default`: the default value for this setting
* `options`: the options for the select type. An object with {"value1": "display string", "value2": "display string"}
* `validate`: Sets more complex validation than the default simple type check.  It uses the Laravel validation syntax.
* `group`: The web ui tab this is under
* `section`: A panel grouping settings in the web ui
* `order`: The order to display this setting within the section

## Predefined Types

* `string`: A string
* `integer`: A number
* `boolean`: A simple toggle switch
* `array`: A list of values that can be added, removed, and re-ordered.
* `select`: A dropdown box with predefined options. Requires the option field.
* `email`: Makes sure that the input has the correct format for an email
* `password`: Masks the value of the input (but does not keep it fully private)

## Custom Types

You can set the type field to a custom type, and define a Vue.js component to show it to the user.

Give the Vue.js component the name "SettingType", where type is the custom type with the first
letter as a capital. Vue.js components are in the `resources/js/components` directory.

This is an empty component with the name SettingType (make sure that you rename it).  It uses the BaseSetting mixin for
basic setting code.  Examine the BaseSetting component.

```vue
<template>
    <div></div>
</template>

<script>
    import BaseSetting from "./BaseSetting";

    export default {
        name: "SettingType",
        mixins: [BaseSetting]
    }
</script>

<style scoped>

</style>
```

How to use Vue.js is not part of this document. Documentation is at [vuejs.org](https://vuejs.org/v2/guide/).
