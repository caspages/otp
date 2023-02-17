import { Plugin } from 'ckeditor5/src/core';
import EditorAdvancedLinkEditing from "./editoradvancedlinkediting";
import EditorAdvancedLinkUi from "./editoradvancedlinkui";

class EditorAdvancedLink extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [EditorAdvancedLinkEditing, EditorAdvancedLinkUi];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'EditorAdvancedLink';
  }
}

export default {
  EditorAdvancedLink,
};
