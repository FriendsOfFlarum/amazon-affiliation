import app from 'flarum/app';
import AmazonSettingsPage from './components/AmazonSettingsPage';

app.initializers.add('fof/amazon-affiliation', () => {
    app.extensionData.for('fof-amazon-affiliation').registerPage(AmazonSettingsPage);
});
