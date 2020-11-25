import app from 'flarum/app';
import AmazonSettingsModal from './components/AmazonSettingsModal';

app.initializers.add('fof/amazon-affiliation', () => {
  app.extensionSettings['fof-amazon-affiliation'] = () => app.modal.show(AmazonSettingsModal);
});
