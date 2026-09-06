import tinymce from 'tinymce';
import 'tinymce/icons/default';
import 'tinymce/themes/silver';
import 'tinymce/models/dom';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/link';
import 'tinymce/plugins/image';
import 'tinymce/plugins/table';
import 'tinymce/plugins/code';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/wordcount';
import 'tinymce/plugins/preview';
import 'tinymce/skins/ui/oxide/skin.min.css';
import contentCss from 'tinymce/skins/content/default/content.min.css?inline';

const textarea = document.querySelector('[data-post-editor]');
if (textarea) {
    const form = textarea.form;
    const font = getComputedStyle(textarea).fontFamily;
    let readyToSubmit = false;
    tinymce.init({
        target: textarea,
        license_key: 'gpl',
        skin: false,
        content_css: false,
        content_style: `${contentCss} body { font-family: ${font}; font-size: 16px; line-height: 1.75; padding: 12px; color: #263c32; } img {max-width:100%;height:auto;} table {border-collapse:collapse;width:100%;} td,th {border:1px solid #ddd;padding:8px;}`,
        height: 560,
        menubar: false,
        promotion: false,
        plugins: 'lists advlist link image table code fullscreen wordcount preview',
        toolbar:
            'undo redo | blocks | bold italic underline | forecolor | alignleft aligncenter alignright | bullist numlist | link image table | removeformat code preview fullscreen',
        block_formats: 'Đoạn văn=p;Tiêu đề 2=h2;Tiêu đề 3=h3;Tiêu đề 4=h4',
        toolbar_mode: 'wrap',
        image_caption: false,
        image_title: true,
        automatic_uploads: true,
        file_picker_types: 'image',
        images_upload_handler: async (blobInfo) => {
            const data = new FormData();
            data.append('file', blobInfo.blob(), blobInfo.filename());
            const response = await fetch(textarea.dataset.uploadUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
                body: data,
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.errors?.file?.[0] || result.message || 'Không tải được ảnh.');
            return result.location;
        },
        setup(editor) {
            editor.on('init', () => {
                textarea.required = false;
            });
            editor.on('change input undo redo', () => editor.save());
            form.addEventListener('submit', async (event) => {
                if (readyToSubmit) return;
                event.preventDefault();
                const button = event.submitter;
                if (button) button.disabled = true;
                try {
                    const uploads = await editor.uploadImages();
                    if (uploads.some((item) => !item.status)) throw new Error('Có ảnh chưa tải xong. Hãy thử lại.');
                    editor.save();
                    if (!editor.getContent({ format: 'text' }).trim() && !editor.getContent().includes('<img')) {
                        throw new Error('Vui lòng nhập nội dung bài viết.');
                    }
                    readyToSubmit = true;
                    if (button) button.disabled = false;
                    form.requestSubmit(button || undefined);
                } catch (error) {
                    editor.notificationManager.open({ text: error.message, type: 'error' });
                } finally {
                    if (button) button.disabled = false;
                }
            });
        },
    });
}
