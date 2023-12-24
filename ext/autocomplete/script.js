let completions_el = document.createElement('ul');
completions_el.className = 'autocomplete_completions';
completions_el.id = 'completions';

// Whenever input changes, look at what word is currently
// being typed, and fetch completions for it.
function updateCompletions(element) {
	highlightCompletion(element, -1);

	let text = element.value;
	let pos = element.selectionStart;

	if(!text) return;

	// get the word before the cursor
	var start = text.lastIndexOf(' ', pos-1);
	if(start === -1) {
		start = 0;
	}
	else {
		start++; // skip the space
	}
	var word = text.substring(start, pos);

	// search for completions
	if(word === '') {
		element.completions = {};
		renderCompletions(element);
	}
	else {
		if(element.completer_timeout !== null) {
			clearTimeout(element.completer_timeout);
			element.completer_timeout = null;
		}
		element.completer_timeout = setTimeout(() => {
			fetch(base_href + '/api/internal/autocomplete?s=' + word).then(
				(response) => response.json()
			).then((json) => {
				if(element.selected_completion !== -1) {
					return; // user has started to navigate the completions, so don't update them
				}
				element.completions = json;
				renderCompletions(element);
			});
		}, 250);
		renderCompletions(element);	
	}
}

// Highlight the nth completion
function highlightCompletion(element, n) {
	if(!element.completions) return;
	element.selected_completion = Math.min(
		Math.max(n, -1),
		Object.keys(element.completions).length-1
	);
	renderCompletions(element);
}

// Render the completion block
function renderCompletions(element) {
	let completions = element.completions;
	let selected_completion = element.selected_completion;

	// if there are no completions, remove the completion block
	if(Object.keys(completions).length === 0) {
		completions_el.remove();
		return;
	}

	// remove all children
	while(completions_el.firstChild) {
		completions_el.removeChild(completions_el.firstChild);
	}

	// add children for each completion, with the selected one highlighted
	Object.keys(completions).forEach((key, i) => {
		let value = completions[key];

		let li = document.createElement('li');
		li.innerHTML = key + ' (' + value + ')';
		if(i === selected_completion) {
			li.className = 'selected';
		}
		// on hover, select the completion
		li.addEventListener('mouseover', () => {
			highlightCompletion(element, i);
		});
		// on click, set the completion
		// (mousedown is used instead of click because click is
		// fired after blur, which causes the completion block to
		// be removed before the click event is handled)
		li.addEventListener('mousedown', () => {
			setCompletion(element, key);
		});
		completions_el.appendChild(li);
	});

	// insert the completion block after the element
	if(element.parentNode) {
		element.parentNode.insertBefore(completions_el, element.nextSibling);
		completions_el.style.width = element.clientWidth + 'px';	
	}
}

// Set the current word to the given completion
function setCompletion(element, new_word) {
	let text = element.value;
	let pos = element.selectionStart;

	// get the word before the cursor
	var start = text.lastIndexOf(' ', pos-1);
	if(start === -1) {
		start = 0;
	}
	else {
		start++; // skip the space
	}
	var end = text.indexOf(' ', pos);
	if(end === -1) {
		end = text.length;
	}

	// replace the word with the completion
	new_word += ' ';
	element.value = text.substring(0, start) + new_word + text.substring(end);
	element.selectionStart = start + new_word.length;
	element.selectionEnd = start + new_word.length;

	// reset metadata
	element.completions = {};
	element.selected_completion = -1;
	if(element.completer_timeout) {
		clearTimeout(element.completer_timeout);
		element.completer_timeout = null;
	}
}

document.addEventListener('DOMContentLoaded', () => {
	// Find all elements with class 'autocomplete_tags'
	document.querySelectorAll('.autocomplete_tags').forEach((element) => {
		// set metadata
		element.completions = {};
		element.selected_completion = -1;
		element.completer_timeout = null;

		// disable built-in autocomplete
		element.setAttribute('autocomplete', 'off');
	
		// when element is focused, add completion block
		element.addEventListener('focus', () => {
			updateCompletions(element);
		});

		// when element is blurred, remove completion block
		element.addEventListener('blur', () => {
			// if we are blurring because we are clicking on a completion,
			// don't remove the completion block until the click event is done
			setTimeout(() => {
				completions_el.remove();
			}, 250);
		});

		// when cursor is moved, change current completion
		document.addEventListener('selectionchange', (event) => {
			if(event.target !== element) {
				return;
			}
			updateCompletions(element);
		});

		element.addEventListener('keydown', (event) => {
			// up / down should select next / previous completion
			if(event.keyCode === 38) {
				event.preventDefault();
				highlightCompletion(element, element.selected_completion-1);
			} else if(event.keyCode === 40) {
				event.preventDefault();
				highlightCompletion(element, element.selected_completion+1);
			}
			// if enter is pressed, add the selected completion
			if(event.keyCode === 13 && element.selected_completion !== -1) {
				event.preventDefault();
				setCompletion(element, Object.keys(element.completions)[element.selected_completion]);
			}
			// if escape is pressed, hide the completion block
			if(event.keyCode === 27) {
				completions_el.remove();
			}
		});

		// on change, update completions
		element.addEventListener('input', (event) => {
			updateCompletions(event);
		});
	});
});
