import app from 'flarum/app';
import SettingsModal from 'flarum/components/SettingsModal';
import Switch from 'flarum/components/Switch';
import locales from '../amazonAssociatesLocales';

const settingsPrefix = 'flagrow-amazon-affiliation.';
const translationPrefix = 'flagrow-amazon-affiliation.admin.settings.';

export default class AmazonSettingsModal extends SettingsModal {
    title() {
        return app.translator.trans(translationPrefix + 'title.modal');
    }

    form() {
        return [
            m('.Form-group', [
                m('label', Switch.component({
                    state: this.setting(settingsPrefix + 'keep-existing-tag')() > 0,
                    onchange: this.setting(settingsPrefix + 'keep-existing-tag'),
                    children: app.translator.trans(translationPrefix + 'field.keep-existing-tag'),
                })),
                m('.helpText', app.translator.trans(translationPrefix + 'field.keep-existing-tag-help')),
            ]),
            m('.Form-group', [
                m('label', Switch.component({
                    state: this.setting(settingsPrefix + 'remove-tag-if-unhandled')() > 0,
                    onchange: this.setting(settingsPrefix + 'remove-tag-if-unhandled'),
                    children: app.translator.trans(translationPrefix + 'field.remove-tag-if-unhandled'),
                })),
                m('.helpText', app.translator.trans(translationPrefix + 'field.remove-tag-if-unhandled-help')),
            ]),
            m('h2', app.translator.trans(translationPrefix + 'title.tags')),
            locales.map(locale => m('.Form-group', [
                m('label', app.translator.trans(translationPrefix + 'field.tag', locale)),
                m('input[type=text].FormControl', {
                    bidi: this.setting(settingsPrefix + 'affiliate-tag.' + locale.domain),
                    placeholder: app.translator.trans(translationPrefix + 'field.tag-placeholder'),
                }),
            ])),
        ];
    }
}
