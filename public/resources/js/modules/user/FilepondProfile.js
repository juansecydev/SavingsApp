document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.querySelector('#profile_picture_input');
    const csrfToken = document.querySelector('input[name="csrf_token"]');

    if (!fileInput) {
        return;
    }

    FilePond.registerPlugin(
        FilePondPluginFileValidateSize,
        FilePondPluginFileValidateType,
        FilePondPluginImageCrop,
        FilePondPluginImageExifOrientation,
        FilePondPluginImagePreview,
        FilePondPluginImageResize,
        FilePondPluginImageTransform
    );

    FilePond.create(fileInput, {
        allowMultiple: false,
        allowImagePreview: true,
        imagePreviewHeight: 170,
        imageCropAspectRatio: '1:1',
        labelIdle: 'Arrastra tu foto o <span class="filepond--label-action">explora</span>',
        acceptedFileTypes: ['image/png', 'image/jpeg'],
        maxFileSize: '5MB',
        allowBrowse: true,
        allowDrop: true,
        allowReplace: true,
        allowImageExifOrientation: true,
        allowImageCrop: true,
        allowProcess: true,
        instantUpload: true,
        server: {
            process: {
                url: '/api/v1/profile-picture/process',
                method: 'POST',
                headers: csrfToken
                    ? {
                        'X-CSRF-Token': csrfToken.value
                    }
                    : {},
                onload: (response) => response,
                onerror: (response) => response
            }
        },
        imageResizeTargetWidth: 200,
        imageResizeTargetHeight: 200,
        stylePanelLayout: 'compact circle',
        styleLoadIndicatorPosition: 'center bottom',
        styleProgressIndicatorPosition: 'right bottom',
        styleButtonRemoveItemPosition: 'left bottom',
        styleButtonProcessItemPosition: 'right bottom',
    });
});
