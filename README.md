# craft-state-helper plugin for Craft CMS 3.x

A simple Craft CMS plugin that provides the ability to submit information which can be stored against a user account, and later retrieved.

This enables, for example, information about which video a user has watched to be recorded, so that on a later page visit we can style the videos such that we can indicate that they've been watched previously by that user.

Note that the user has to be logged in, in order to be able to save or retrieve state.

![Screenshot](resources/img/plugin-logo.png)

## Requirements

This plugin requires Craft CMS 3.0.0-beta.23 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require nzmebooks/craft-state-helper

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for craft-state-helper.

## Using craft-state-helper

### Posting state from the client

We use the [method espoused by Craft](https://craftcms.com/docs/plugins/controllers#posting-to-controller-actions), whereby we use a hidden form and post via ajax:

    <form id="statehelper-form" class="statehelper-form" action="" method="POST">
        {{ csrfInput() }}
        <input type="hidden" name="action" value="statehelper/statehelper/save-state">
        <input type="hidden" name="name">
        <input type="hidden" name="value">
    </form>

    <script>
        // Presumably, we run the following on receiving a suitable event
        $.post('/', $("#statehelper-form").serialize())
            .done(function(data) {
                if (data.success) {
                    // Whatever
                } else {
                    // Error
                }
            });
    </script>

We can also delete state:

    <form id="statehelper-form" class="statehelper-form" action="" method="POST">
        {{ csrfInput() }}
        <input type="hidden" name="action" value="statehelper/statehelper/delete-state">
        <input type="hidden" name="name">
        <input type="hidden" name="value">
    </form>

### Retrieving state in twig templates

	{# obtain the value using the 'getState' variable #}
    {% set value = craft.statehelper.getState( NAME ) %}

Brought to you by [meBooks](https://mebooks.co.nz)
