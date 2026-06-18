import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';
import type AdminPage from 'flarum/admin/components/AdminPage';
import locales from './amazonAssociatesLocales';

const translationPrefix = 'fof-amazon-affiliation.admin.settings.';

export default [
  new Extend.Admin() //
    .setting(
      () => ({
        setting: 'fof-amazon-affiliation.rich-card',
        type: 'boolean',
        label: app.translator.trans(translationPrefix + 'field.rich-card'),
        help: app.translator.trans(translationPrefix + 'field.rich-card-help'),
      }),
      110
    )
    .setting(
      () => ({
        setting: 'fof-amazon-affiliation.keep-existing-tag',
        type: 'boolean',
        label: app.translator.trans(translationPrefix + 'field.keep-existing-tag'),
        help: [
          app.translator.trans(translationPrefix + 'field.keep-existing-tag-help'),
          app.translator.trans(translationPrefix + 'field.help-mediaembed'),
        ],
      }),
      100
    )
    .setting(
      () => ({
        setting: 'fof-amazon-affiliation.remove-tag-if-unhandled',
        type: 'boolean',
        label: app.translator.trans(translationPrefix + 'field.remove-tag-if-unhandled'),
        help: [
          app.translator.trans(translationPrefix + 'field.remove-tag-if-unhandled-help'),
          app.translator.trans(translationPrefix + 'field.help-mediaembed'),
        ],
      }),
      90
    )

    // Tags section heading.
    .customSetting(
      () => [
        <h2>{app.translator.trans(translationPrefix + 'title.tags')}</h2>,
        <div className="helpText">{app.translator.trans(translationPrefix + 'title.tags-mediaembed')}</div>,
      ],
      80
    )

    // One affiliate tag field per Amazon Associates locale.
    .customSetting(function (this: AdminPage) {
      return locales.map((locale) =>
        this.buildSettingComponent({
          type: 'text',
          setting: 'fof-amazon-affiliation.affiliate-tag.' + locale.domain,
          label: app.translator.trans(translationPrefix + 'field.tag', { ...locale }),
          placeholder: app.translator.trans(translationPrefix + 'field.tag-placeholder'),
        })
      );
    }, 70),
];
