import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Switch from 'flarum/common/components/Switch';
import locales from './../amazonAssociatesLocales';

const settingsPrefix = 'fof-amazon-affiliation.';
const translationPrefix = 'fof-amazon-affiliation.admin.settings.';

export default class AmazonSettingsPage extends ExtensionPage {
  oninit(vnode) {
    super.oninit(vnode);
  }

  content() {
    return [
      m('.container', [
        m('.Form-group', [
          Switch.component(
            {
              state: this.setting(settingsPrefix + 'keep-existing-tag')() > 0,
              onchange: this.setting(settingsPrefix + 'keep-existing-tag'),
            },
            app.translator.trans(translationPrefix + 'field.keep-existing-tag')
          ),
          m('.helpText', app.translator.trans(translationPrefix + 'field.keep-existing-tag-help')),
          m('.helpText', app.translator.trans(translationPrefix + 'field.help-mediaembed')),
        ]),
        m('.Form-group', [
          Switch.component(
            {
              state: this.setting(settingsPrefix + 'remove-tag-if-unhandled')() > 0,
              onchange: this.setting(settingsPrefix + 'remove-tag-if-unhandled'),
            },
            app.translator.trans(translationPrefix + 'field.remove-tag-if-unhandled')
          ),
          m('.helpText', app.translator.trans(translationPrefix + 'field.remove-tag-if-unhandled-help')),
          m('.helpText', app.translator.trans(translationPrefix + 'field.help-mediaembed')),
        ]),
        m('h2', app.translator.trans(translationPrefix + 'title.tags')),
        m('.helpText', app.translator.trans(translationPrefix + 'title.tags-mediaembed')),
        locales.map((locale) =>
          m('.Form-group', [
            m('label', app.translator.trans(translationPrefix + 'field.tag', locale)),
            m('input[type=text].FormControl', {
              bidi: this.setting(settingsPrefix + 'affiliate-tag.' + locale.domain),
              placeholder: app.translator.trans(translationPrefix + 'field.tag-placeholder'),
            }),
          ])
        ),
        this.submitButton(),
      ]),
    ];
  }
}
