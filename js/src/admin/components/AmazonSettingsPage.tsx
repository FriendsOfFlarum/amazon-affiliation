import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Switch from 'flarum/common/components/Switch';
import locales from '../amazonAssociatesLocales';

const settingsPrefix = 'fof-amazon-affiliation.';
const translationPrefix = 'fof-amazon-affiliation.admin.settings.';

export default class AmazonSettingsPage extends ExtensionPage {
  content() {
    return (
      <div className="container">
        <div className="Form-group">
          <Switch
            state={(this.setting(settingsPrefix + 'keep-existing-tag')() as number) > 0}
            onchange={this.setting(settingsPrefix + 'keep-existing-tag')}
          >
            {app.translator.trans(translationPrefix + 'field.keep-existing-tag')}
          </Switch>
          <div className="helpText">{app.translator.trans(translationPrefix + 'field.keep-existing-tag-help')}</div>
          <div className="helpText">{app.translator.trans(translationPrefix + 'field.help-mediaembed')}</div>
        </div>

        <div className="Form-group">
          <Switch
            state={(this.setting(settingsPrefix + 'remove-tag-if-unhandled')() as number) > 0}
            onchange={this.setting(settingsPrefix + 'remove-tag-if-unhandled')}
          >
            {app.translator.trans(translationPrefix + 'field.remove-tag-if-unhandled')}
          </Switch>
          <div className="helpText">{app.translator.trans(translationPrefix + 'field.remove-tag-if-unhandled-help')}</div>
          <div className="helpText">{app.translator.trans(translationPrefix + 'field.help-mediaembed')}</div>
        </div>

        <h2>{app.translator.trans(translationPrefix + 'title.tags')}</h2>
        <div className="helpText">{app.translator.trans(translationPrefix + 'title.tags-mediaembed')}</div>

        {locales.map((locale) => (
          <div className="Form-group">
            <label>{app.translator.trans(translationPrefix + 'field.tag', { ...locale })}</label>
            <input
              type="text"
              className="FormControl"
              bidi={this.setting(settingsPrefix + 'affiliate-tag.' + locale.domain)}
              placeholder={app.translator.trans(translationPrefix + 'field.tag-placeholder')}
            />
          </div>
        ))}

        {this.submitButton()}
      </div>
    );
  }
}
