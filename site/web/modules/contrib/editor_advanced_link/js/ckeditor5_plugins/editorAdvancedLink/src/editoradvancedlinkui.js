import { Plugin } from 'ckeditor5/src/core';
import { LabeledFieldView, createLabeledInputText } from 'ckeditor5/src/ui';
import { additionalFormElements} from './utils';

export default class EditorAdvancedLinkUi extends Plugin {
  init() {
    const enabledModelNames = this.editor.plugins.get('EditorAdvancedLinkEditing').enabledModelNames;
    this._changeFormToVertical();
    enabledModelNames.reverse().forEach((modelName) => {
      this._createExtraFormField(modelName, additionalFormElements[modelName]);
      this._handleDataLoadingIntoExtraFormField(modelName);
    });
    this._handleExtraFormFieldSubmit(enabledModelNames);
  }

  _changeFormToVertical() {
    const linkFormView = this.editor.plugins.get( 'LinkUI' ).formView;
    linkFormView.extendTemplate( {
      attributes: {
        class: [ 'ck-vertical-form', 'ck-link-form_layout-vertical' ]
      }
    } );
  }

  _createExtraFormField(modelName, options) {
    const editor = this.editor;
    const locale = editor.locale;
    const linkFormView = editor.plugins.get( 'LinkUI' ).formView;

    const extraFieldView = new LabeledFieldView( locale, createLabeledInputText );
    extraFieldView.label = options.label;

    linkFormView.children.add( extraFieldView, 1 );

    linkFormView.on( 'render', () => {
      linkFormView._focusables.add( extraFieldView, 1 );
      linkFormView.focusTracker.add( extraFieldView.element );
    } );

    linkFormView[modelName] = extraFieldView;
  }

  _handleExtraFormFieldSubmit(modelNames) {
    const editor = this.editor;
    const linkFormView = editor.plugins.get( 'LinkUI' ).formView;
    const linkCommand = editor.commands.get( 'link' );

    this.listenTo( linkFormView, 'submit', () => {
      const values = modelNames.reduce((state, modelName) => {
        state[modelName] = linkFormView[modelName].fieldView.element.value;
        return state;
      }, {});
      // Stop the execution of the link command caused by closing the form.
      // Inject the extra attribute value. The highest priority listener here
      // injects the argument (here below 👇).
      // - The high priority listener in
      //   _addExtraAttributeOnLinkCommandExecute() gets that argument and sets
      //   the extra attribute.
      // - The normal (default) priority listener in ckeditor5-link sets
      //   (creates) the actual link.
      linkCommand.once( 'execute', ( evt, args ) => {
        if (args.length < 3) {
          args.push( values );
        } else if (args.length === 3) {
          Object.assign(args[2], values);
        } else {
          throw Error('The link command has more than 3 arguments.')
        }
      }, { priority: 'highest' } );
    }, { priority: 'high' } );
  }

  _handleDataLoadingIntoExtraFormField(modelName) {
    const editor = this.editor;
    const linkCommand = editor.commands.get( 'link' );
    const linkFormView = editor.plugins.get( 'LinkUI' ).formView;

    linkFormView[modelName].fieldView.bind( 'value' ).to( linkCommand, modelName );

    // This is a hack. Thic could be potentially improved by detecting when the
    // form is added by checking the collection of the ContextualBalloon plugin.
    editor.plugins.get( 'ContextualBalloon' )._rotatorView.content.on( 'add', ( evt, view ) => {
      if ( view !== linkFormView ) {
        return;
      }

      // Note: Copy & pasted from LinkUI.
      // https://github.com/ckeditor/ckeditor5/blob/f0a093339631b774b2d3422e2a579e27be79bbeb/packages/ckeditor5-link/src/linkui.js#L333-L333
      linkFormView[modelName].fieldView.element.value = linkCommand[ modelName ] || '';
    } );
  }

}
