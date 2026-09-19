document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.querySelector('#profile_picture_input');
    const csrfToken = document.querySelector('input[name="csrf_token"]');
    const toastElement = document.querySelector('#profile-picture-toast');
    const toastTitle = document.querySelector('#profile-picture-toast-title');
    const toastMessage = document.querySelector('#profile-picture-toast-message');

    if (!fileInput || !toastElement || !toastTitle || !toastMessage) {
        return;
    }

    const showToast = (title, message, type) => {
        toastElement.classList.remove('text-bg-success', 'text-bg-danger', 'text-bg-warning');
        toastElement.classList.add(`text-bg-${type}`);
        toastTitle.textContent = title;
        toastMessage.textContent = message;

        bootstrap.Toast.getOrCreateInstance(toastElement, {
            autohide: true,
            delay: 4000
        }).show();
    };

    FilePond.registerPlugin(
        FilePondPluginFileValidateSize,
        FilePondPluginFileValidateType,
        FilePondPluginImageCrop,
        FilePondPluginImageExifOrientation,
        FilePondPluginImagePreview,
        FilePondPluginImageResize,
        FilePondPluginImageTransform
    );

    const pond = FilePond.create(fileInput, {
        allowMultiple: false,
        allowImagePreview: true,
        imagePreviewHeight: 170,
        imageCropAspectRatio: '1:1',
        labelIdle: 'Arrastra tu foto o <span class="filepond--label-action">explora</span>',
        acceptedFileTypes: ['image/png', 'image/jpeg'],
        maxFileSize: '2MB',
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

    pond.on('addfile', (error, file) => {
        if (error) {
            showToast('Error', error.main || 'No se pudo preparar la imagen.', 'danger');
            return;
        }

        showToast(
            'Imagen lista',
            `La imagen tiene una vista previa y está lista para subir.`,
            'success'
        );
    });

    pond.on('removefile', (error) => {
        if (error) {
            showToast('Error', 'No se pudo eliminar la imagen.', 'danger');
            return;
        }

        showToast('Imagen eliminada', 'La imagen fue eliminada.', 'warning');
    });

    pond.on('processfile', (error, file) => {
        if (error) {
            showToast('Error al subir', error.main || 'No se pudo subir la imagen.', 'danger');
            return;
        }

        showToast('Carga exitosa', `Para confirmar, presiona el botón de actualizar datos.`, 'success');
    });
});
