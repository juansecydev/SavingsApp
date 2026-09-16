document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.querySelector('#profile_picture_input');
    const profilePictureField = document.querySelector('#profile_picture');

    if (!fileInput) {
        return;
    }

    const existingProfilePicture = profilePictureField && profilePictureField.value
        ? profilePictureField.value
        : null;

    const pond = FilePond.create(fileInput, {
        allowMultiple: false,
        allowImagePreview: true,
        imagePreviewHeight: 220,
        imageCropAspectRatio: '1:1',
        stylePanelLayout: 'circle',
        styleLoadIndicatorPosition: 'center center',
        styleProgressIndicatorPosition: 'center center',
        labelIdle: 'Arrastra tu foto o <span class="filepond--label-action">explora</span>',
        labelFileProcessing: 'Subiendo',
        labelFileProcessingComplete: 'Listo',
        acceptedFileTypes: ['image/png', 'image/jpeg'],
        maxFileSize: '5MB',
        allowBrowse: true,
        allowDrop: true,
        allowReplace: true,
        allowRevert: false,
        files: existingProfilePicture ? [{
            source: existingProfilePicture,
            options: {
                type: 'local'
            }
        }] : []
    });

    pond.on('addfile', (error, file) => {
        if (error) {
            console.error(error);
            return;
        }

        if (file && file.file) {
            profilePictureField.value = file.file.name;
        }
    });

    pond.on('removefile', () => {
        if (profilePictureField) {
            profilePictureField.value = '';
        }
    });

    if (existingProfilePicture && profilePictureField) {
        profilePictureField.value = existingProfilePicture;
    }
});
