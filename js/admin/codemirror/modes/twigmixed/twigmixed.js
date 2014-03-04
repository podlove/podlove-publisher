/**
 * HTML Mixed Mode for Twig
 * 
 * @author eric@ericteubert.de
 */
CodeMirror.defineMode("twigmixed", function(config, parserConfig) {

	//config settings
	var scriptStartRegex = /^(\{\{|\{%|\{#)/,
	    scriptEndRegex = /^(\}|%|#)\}/;

	var twigMode = CodeMirror.getMode(config, "twig"),
	    htmlMixedMode = CodeMirror.getMode(config, "htmlmixed");

	parsers = {
		html: function (stream, state) {;
			return htmlMixedMode.token(stream, state.htmlState);
		},
		twig: function (stream, state) {
			return twigMode.token(stream, state.twigState);
		}
	};

	return {
		startState: function() {
			return {
			    token: parsers.html,
			    htmlState: CodeMirror.startState(htmlMixedMode),
			    twigState: CodeMirror.startState(twigMode)
			};
		},

		token: function(stream, state) {

			if (state.token != parsers.twig && stream.match(scriptStartRegex, false)) {
				stream.match(scriptStartRegex, true);
				state.token = parsers.twig;
				stream.backUp(2);
				return "bracket";
			};

			if (state.token == parsers.twig && stream.match(scriptEndRegex, false)) {
				twigMode.token(stream, state.twigState);
				state.token = parsers.html;
				return "bracket";
			};

			return state.token(stream, state);
		},

		copyState: function(state) {
			return {
				token: state.token,
				htmlState: CodeMirror.copyState(htmlMixedMode, state.htmlState),
				twigState: CodeMirror.copyState(twigMode, state.twigState)
			};
		},
	};
}, "htmlmixed");

CodeMirror.defineMIME("application/x-twig", { name: "twigmixed", scriptingModeSpec:"twig"});