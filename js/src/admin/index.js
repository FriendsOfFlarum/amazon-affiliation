import app from 'flarum/app';
import AmazonSettingsModal from './components/AmazonSettingsModal';

app.initializers.add('flagrow-amazon-affiliation', app => {
    app.extensionSettings['flagrow-amazon-affiliation'] = () => app.modal.show(new AmazonSettingsModal());
});
