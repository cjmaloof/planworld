(function() {

tinyMCE.init({
    mode: 'none',
    theme: 'advanced',
    theme_advanced_buttons1: 'bold,italic,underline,strikethrough,separator,undo,redo,separator,bullist,numlist,link,unlink,image,media,hr',
    theme_advanced_buttons2: '',
    theme_advanced_buttons3: '',
    theme_advanced_toolbar_location: 'top',
    theme_advanced_toolbar_align: 'left'
});

var lastFormat = $('#formatType').value;
function addParagraphs(text) {
    var paragraphs = text.split('\n\n');
    var converted = [];
    for(var i = 0, len = paragraphs.length; i < len; ++i) {
        converted.push('<p>' + paragraphs[i] + '</p>\n');
    }
    return converted.join('');
};
function removeParagraphs(text) {
    return text.replace(/<p>/gi, '').replace(/<\/p>/gi, '\n');
};

this.toggleEditor = function() {
    if(!tinyMCE.getInstanceById('inputTextArea')) {
        lastFormat = $('#formatType').val();
        if(lastFormat == 'text') {
            $('#inputTextArea').val(addParagraphs($('#inputTextArea').val()));
        }

        tinyMCE.execCommand('mceAddControl', false, 'inputTextArea');
        $('#toggleEditor').html('Text Editor');
        $('#formatType').val('html');
    } else { 
        tinyMCE.execCommand('mceRemoveControl', false, 'inputTextArea');
        $('#toggleEditor').html('WYSIWYG Editor');
        $('#formatType').val(lastFormat);
        if(lastFormat == 'text') {
            $('#inputTextArea').val(removeParagraphs($('#inputTextArea').val()));
        }
    }
    return false;
};

function autoSave() {
    var ed = tinyMCE.getInstanceById('inputTextArea');
    if(ed) {
        $('#inputTextArea').val(ed.getContent());
    }

    $('#editForm').ajaxSubmit({
        beforeSubmit: function(data) {
            data.push({ name: 'draft', value: 'save draft' });
        }
    });
};

this.ajaxSave = autoSave;

this.startAutosaveTimer = function() {
    window.setInterval(autoSave, 5 * 60 * 1000);
};

})();
