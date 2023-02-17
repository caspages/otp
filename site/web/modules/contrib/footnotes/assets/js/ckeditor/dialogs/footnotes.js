/**
 * @file
 */

function footnotesDialog(editor, isEdit) {
  return {
    title: Drupal.t("Footnotes Dialog"),
    minWidth: 500,
    minHeight: 50,
    contents: [
      {
        id: "info",
        label: Drupal.t("Add a footnote"),
        title: Drupal.t("Add a footnote"),
        elements: [
          {
            id: "footnote",
            type: "textarea",
            label: Drupal.t("Footnote text :"),
            setup(element) {
              if (isEdit) {
                this.setValue(element.getHtml());
              }
            }
          },
          {
            id: "value",
            type: "text",
            label: Drupal.t("Value :"),
            setup(element) {
              if (isEdit) {
                this.setValue(element.getAttribute("value"));
              }
            }
          }
        ]
      }
    ],
    onShow() {
      if (isEdit) {
        this.fakeObj = CKEDITOR.plugins.footnotes.getSelectedFootnote(editor);
        this.realObj = editor.restoreRealElement(this.fakeObj);
      }
      this.setupContent(this.realObj);
    },
    onOk() {
      CKEDITOR.plugins.footnotes.createFootnote(
        editor,
        this.realObj,
        this.getValueOf("info", "footnote"),
        this.getValueOf("info", "value")
      );
      delete this.fakeObj;
      delete this.realObj;
    }
  };
}

CKEDITOR.dialog.add("createfootnotes", editor => footnotesDialog(editor));
CKEDITOR.dialog.add("editfootnotes", editor => footnotesDialog(editor, 1));
