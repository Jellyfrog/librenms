# Creating Documentation

One goal of the LibreNMS project: users can get all the
help that they need from our documentation.

The documentation uses the [markdown](https://en.wikipedia.org/wiki/Markdown)
markup language. The system makes it with [mkdocs](https://www.mkdocs.org/). To edit
or create markdown, you only need a text editor. But we recommend that you build
your docs before you submit them, to examine them visually. This
page has instructions for this step.

## Writing docs

When you add a new feature or extension, we need full
documentation with it. This is easy to do:

- Find the applicable directory for your new document. General, Support
  and Extensions are the most probable selections.
- Select a name that is not too long, and that tells what the document is.
  It must match what users look for, or tell about the feature.
- Add the new document into the `nav` section of `mkdocs.yml` if it must
  show in the table of contents
- Make sure that the first line contains: `source: path/to/file.md` - do not include the
  initial `doc/`.
- In the body of the document, give the necessary details, but keep the text simple. Some tips:
  - If the document can apply to different distros, such as CentOS and Ubuntu,
    try to include the information for all of them. If that is not possible, put in a
placeholder that asks for contributions.
  - Make sure that you use the correct format for `commands` and `code blocks`.
    Put one liners in backticks, and blocks in ```.
  - Put content into sub-headings where possible, to give the content structure.
- If you rename a file, add a redirect for the old file in `mkdocs.yml` like this:
```yaml
  - redirects:
      redirect_maps:
        'old/page.md': 'new/page.md'
```

Make sure that you add the document to the applicable section in `pages` of
`mkdocs.yml`. Then it is in the correct menu, and the system builds it.  If you do not do this
step, your document stays invisible :)

## Formatting docs

Our docs are based on Markdown with mkdocs, which obeys the markdown specifications and
nothing more. Because of that, we also import some more libraries:

- pymdownx.tasklist
- pymdownx.tilde

This means you can use:

- `~~strikethrough~~` to make ~~strikethrough~~
- [X] `- [X] List items`
- You can make Urls `[like this](https://www.librenms.org)` [like this](https://www.librenms.org)
- You can put code in \`\` for a single line, or \`\`\` for multiple lines.
- You can use `#` for main headings. This becomes a `<h1>` tag.
  When you increase the `#`'s, the hX tags increase.
- You can use `###` for sub-headings, which show in the TOC to the left.
- Give settings the prefix `!!! setting "<webui setting path>"`

[Markdown CheatSheet Link](https://github.com/adam-p/markdown-here/wiki/Markdown-Cheatsheet)


## Building docs

You do this with `mkdocs`, a python package.

1. Install the necessary packages.

Make a new virtual environment and activate it:

```
python -m venv .python_venvs/docs
source .python_venvs/docs/bin/activate
```

```
pip install \
 markdown-exec \
 markdown-include \
 mkdocs \
 mkdocs-awesome-pages-plugin \
 mkdocs-exclude \
 mkdocs-git-revision-date-localized-plugin \
 mkdocs-include-dir-to-nav \
 mkdocs-macros-plugin \
 mkdocs-material \
 mkdocs-minify-plugin \
 mkdocs-redirects \
 pymdown-extensions
```
If permission problems occur, you can possibly correct them with the
user option, with the user that you build as, for example, `-u librenms`

2. A configuration file to build LibreNMS docs is included in the
distribution: `/opt/librenms/mkdocs.yml`. The configuration
directives are documented
[here](https://www.mkdocs.org/user-guide/configuration/).

3. Build from the librenms base directory: `cd /opt/librenms`.

4. The build is simple:

```
mkdocs build
```

This writes all the documentation in html format to `/opt/librenms/out`
(commits ignore this folder).


## Viewing docs

mkdocs includes its own light-weight webserver for this purpose.

To see the docs, run this command:

```
$ mkdocs serve
INFO    -  Building documentation...
<..>
INFO    -  Documentation built in 12.54 seconds
<..>
INFO    -  Serving on http://127.0.0.1:8000
<..>
INFO    -  Start watching changes
```

Now you find the full set of LibreNMS documentation when you open your
browser at `localhost:8000`.

It is not necessary to `build` before you look at the docs. The `serve` command
does this for you. Also, the server updates the documents that it serves
each time the markdown changes, for example in a different terminal.

### Viewing docs from another machine

By default, the server listens only for connections from the local machine.
If you build on a different machine, you can use this directive
to listen on all interfaces:

```
mkdocs serve --dev-addr=0.0.0.0:8000
```

WARNING: this is not a secure webserver. The risk is yours. Use
applicable host security, and do not keep the server on.
