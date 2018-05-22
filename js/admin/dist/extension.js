'use strict';

System.register('flagrow/amazon-affiliation/amazonAssociatesLocales', [], function (_export, _context) {
    "use strict";

    return {
        setters: [],
        execute: function () {
            _export('default', [{
                domain: 'com',
                name: 'United States',
                central: 'https://affiliate-program.amazon.com/'
            }, {
                domain: 'co.uk',
                name: 'United Kingdom',
                central: 'https://affiliate-program.amazon.co.uk/'
            }, {
                domain: 'de',
                name: 'Deutschland',
                central: 'https://partnernet.amazon.de/'
            }, {
                domain: 'fr',
                name: 'France',
                central: 'https://partenaires.amazon.fr/'
            }, {
                domain: 'co.jp',
                name: 'Japan',
                central: 'https://affiliate.amazon.co.jp/'
            }, {
                domain: 'ca',
                name: 'Canada',
                central: 'https://associates.amazon.ca/'
            }, {
                domain: 'cn',
                name: 'China',
                central: 'https://associates.amazon.cn/'
            }, {
                domain: 'it',
                name: 'Italia',
                central: 'https://programma-affiliazione.amazon.it/'
            }, {
                domain: 'es',
                name: 'España',
                central: 'https://afiliados.amazon.es/'
            }, {
                domain: 'in',
                name: 'India',
                central: 'https://affiliate-program.amazon.in/'
            }, {
                domain: 'com.br',
                name: 'Brazil',
                central: 'https://associados.amazon.com.br/'
            }, {
                domain: 'com.mx',
                name: 'Mexico',
                central: 'https://afiliados.amazon.com.mx/'
            }, {
                domain: 'com.au',
                name: 'Australia',
                central: 'https://affiliate-program.amazon.com.au/'
            }]);
        }
    };
});;
'use strict';

System.register('flagrow/amazon-affiliation/components/AmazonSettingsModal', ['flarum/app', 'flarum/components/SettingsModal', 'flarum/components/Switch', 'flagrow/amazon-affiliation/amazonAssociatesLocales'], function (_export, _context) {
    "use strict";

    var app, SettingsModal, Switch, locales, settingsPrefix, translationPrefix, AmazonSettingsModal;
    return {
        setters: [function (_flarumApp) {
            app = _flarumApp.default;
        }, function (_flarumComponentsSettingsModal) {
            SettingsModal = _flarumComponentsSettingsModal.default;
        }, function (_flarumComponentsSwitch) {
            Switch = _flarumComponentsSwitch.default;
        }, function (_flagrowAmazonAffiliationAmazonAssociatesLocales) {
            locales = _flagrowAmazonAffiliationAmazonAssociatesLocales.default;
        }],
        execute: function () {
            settingsPrefix = 'flagrow-amazon-affiliation.';
            translationPrefix = 'flagrow-amazon-affiliation.admin.settings.';

            AmazonSettingsModal = function (_SettingsModal) {
                babelHelpers.inherits(AmazonSettingsModal, _SettingsModal);

                function AmazonSettingsModal() {
                    babelHelpers.classCallCheck(this, AmazonSettingsModal);
                    return babelHelpers.possibleConstructorReturn(this, (AmazonSettingsModal.__proto__ || Object.getPrototypeOf(AmazonSettingsModal)).apply(this, arguments));
                }

                babelHelpers.createClass(AmazonSettingsModal, [{
                    key: 'title',
                    value: function title() {
                        return app.translator.trans(translationPrefix + 'title.modal');
                    }
                }, {
                    key: 'form',
                    value: function form() {
                        var _this2 = this;

                        return [m('.Form-group', [m('label', Switch.component({
                            state: this.setting(settingsPrefix + 'keep-existing-tag')() > 0,
                            onchange: this.setting(settingsPrefix + 'keep-existing-tag'),
                            children: app.translator.trans(translationPrefix + 'field.keep-existing-tag')
                        })), m('.helpText', app.translator.trans(translationPrefix + 'field.keep-existing-tag-help'))]), m('.Form-group', [m('label', Switch.component({
                            state: this.setting(settingsPrefix + 'remove-tag-if-unhandled')() > 0,
                            onchange: this.setting(settingsPrefix + 'remove-tag-if-unhandled'),
                            children: app.translator.trans(translationPrefix + 'field.remove-tag-if-unhandled')
                        })), m('.helpText', app.translator.trans(translationPrefix + 'field.remove-tag-if-unhandled-help'))]), m('h2', app.translator.trans(translationPrefix + 'title.tags')), locales.map(function (locale) {
                            return m('.Form-group', [m('label', app.translator.trans(translationPrefix + 'field.tag', locale)), m('input[type=text].FormControl', {
                                bidi: _this2.setting(settingsPrefix + 'affiliate-tag.' + locale.domain),
                                placeholder: app.translator.trans(translationPrefix + 'field.tag-placeholder')
                            })]);
                        })];
                    }
                }]);
                return AmazonSettingsModal;
            }(SettingsModal);

            _export('default', AmazonSettingsModal);
        }
    };
});;
'use strict';

System.register('flagrow/amazon-affiliation/main', ['flarum/app', 'flagrow/amazon-affiliation/components/AmazonSettingsModal'], function (_export, _context) {
    "use strict";

    var app, AmazonSettingsModal;
    return {
        setters: [function (_flarumApp) {
            app = _flarumApp.default;
        }, function (_flagrowAmazonAffiliationComponentsAmazonSettingsModal) {
            AmazonSettingsModal = _flagrowAmazonAffiliationComponentsAmazonSettingsModal.default;
        }],
        execute: function () {

            app.initializers.add('flagrow-amazon-affiliation', function (app) {
                app.extensionSettings['flagrow-amazon-affiliation'] = function () {
                    return app.modal.show(new AmazonSettingsModal());
                };
            });
        }
    };
});