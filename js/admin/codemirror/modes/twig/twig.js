/**
 * Twig Mode
 * 
 * @author eric@ericteubert.de
 */
CodeMirror.defineMode("twig", function() {
    var keywords = [
		"autoescape", "block", "do", "embed", "extends", "filter", "flush",
		"for", "from", "if", "import", "include", "macro", "sandbox",
		"set", "spaceless", "use", "verbatim", "abs", "batch", "capitalize",
		"convert_encoding", "date", "date_modify", "default", "escape",
		"first", "format", "join", "json_encode", "keys", "last", "length",
		"lower", "merge", "nl2br", "number_format", "raw", "replace",
		"reverse", "round", "slice", "sort", "split", "striptags", "title",
		"trim", "upper", "url_encode", "attribute", "block", "constant",
		"cycle", "date", "dump", "include", "max", "min", "parent", "random",
		"range", "source", "template_from_string", "constant", "defined",
		"divisibleby", "empty", "even", "iterable", "null", "odd", "sameas",
		"in", "endfor", "endif", "else", "is", "not"
    ];
    keywords = new RegExp("^((" + keywords.join(")|(") + "))\\b");

    function tokenBase (stream, state) {
        var ch = stream.next();
        if (ch == "{") {
            if (ch = stream.eat(/\{|%|#/)) {
                state.tokenize = inTag(ch);
                return "bracket";
            }
        }
    }
    function inTag (close) {

    	close = close == '{' ? '}' : close;

        return function (stream, state) {
            
            if (stream.match(keywords)) {
                return "keyword";
            }
            
            if (stream.match(/[-+]?[0-9]*\.?[0-9]+/)) {
            	return "number";
            };

            var ch = stream.next();
            
            if (ch == close && stream.peek() == '}') {
            	stream.eat('}');
                state.tokenize = tokenBase;
                return "bracket";
            }

            if (close == "#") {
            	return "comment";
            }

            if (ch.match(/[~+-><=!/]/)) {
            	return "operator";
            };

            if (ch.match(/[|\{\}\[\]\(\),:]/)) {
            	return "meta";
            };
            
            if (ch.match(/['"]/) && stream.match(new RegExp('[^' + ch + ']*' + ch))) {
            	return "string";
            };

            if (ch.match(/[a-zA-Z]/) && stream.match(/([\w.]+)/)) {
        	    return "variable";
            }

            return "string";
        };
    }
    return {
        startState: function () {
            return {tokenize: tokenBase};
        },
        token: function (stream, state) {
            return state.tokenize(stream, state);
        }
    };
});

CodeMirror.defineMIME("text/x-twig", "twig");