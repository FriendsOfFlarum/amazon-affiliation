import app from 'flarum/admin/app';
import AmazonSettingsPage from './components/AmazonSettingsPage';

app.initializers.add('fof/amazon-affiliation', () => {
  app.registry.for('fof-amazon-affiliation').registerPage(AmazonSettingsPage);
});
