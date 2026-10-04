# Creating Documentation

One goal of the LibreNMS project is complete help for the users in our
documentation.

The documentation uses the
[markdown](https://en.wikipedia.org/wiki/Markdown) markup language.
[Zensical](https://zensical.org/) generates the site. A text editor is
enough to edit or create markdown. Build your documents before you
submit them, so that you can examine the result. A section on this page
gives the instructions.

## Writing docs

A new feature or extension needs full documentation. These are the
steps:

- Find the correct directory for your new document. General, Support,
  and Extensions are the most common choices.
- Give the document a short, descriptive name. The name must match the
  search terms of the user or describe the feature.
- To put the document into the table of contents, add it to the `nav`
  section of `doc/.nav.yml`.
- Put `source: path/to/file.md` on the first line. Do not include the
  leading `doc/`.
- In the body of the document, be descriptive and simple. Some advice:
    - If the document applies to more than one distribution, such as
    CentOS and Ubuntu, give the information for all of them. If you
    cannot do this, add a placeholder with a request for contributions.
    - Use the correct format for `commands` and `code blocks`. Put a
    single line in backticks and a block in triple backticks.
    - Use subheadings to organise the content.
- After you rename a file, add a redirect for the old file in
  `doc/zensical.toml`:
```toml
[project.plugins.redirects.redirect_maps]
"old/page.md" = "new/page.md"
```

Add the document to the correct section in `nav` of `doc/.nav.yml`. The
document is then in the correct menu. Without this step, the document
never appears.

## Formatting docs

Our documents use Markdown with Zensical. Zensical obeys the markdown
specification only. We therefore import two extra libraries:

- pymdownx.tasklist
- pymdownx.tilde

You can therefore use:

- `~~strikethrough~~` to perform ~~strikethrough~~
- [X] `- [X] List items`
- Url's can be made `[like this](https://www.librenms.org)` [like this](https://www.librenms.org)
- Code can be placed in \`\` for single line or \`\`\` for multiline.
- `#` gives a main heading. It becomes an `<h1>` tag. More `#`
  characters give a higher hX number.
- `###` gives a subheading. A subheading appears in the table of
  contents on the left.
- Put `!!! setting "<webui setting path>"` before a setting.

[Markdown CheatSheet Link](https://github.com/adam-p/markdown-here/wiki/Markdown-Cheatsheet)


## Building docs

`zensical` is a Python package. It builds the documents.

1. Install Zensical in a new virtual environment:

```
python -m venv .python_venvs/docs
source .python_venvs/docs/bin/activate
pip install zensical
```

2. The configuration file for the LibreNMS docs is
`/opt/librenms/doc/zensical.toml`. The [Zensical
documentation](https://zensical.org/docs/setup/basics/) describes the
settings.

3. Build from the librenms base directory: `cd /opt/librenms`.

4. Run the build:

```
zensical build --config-file doc/zensical.toml
```

The command writes all the documentation in HTML format to
`/opt/librenms/doc/out`. Git ignores this folder.

You can also use the official Docker image instead of installing
Zensical:

```
docker run --rm -v "$(pwd):/librenms" -w /librenms zensical/zensical build --config-file doc/zensical.toml
```

## Viewing docs

Zensical has its own small web server for this purpose.

Run this command:

```
zensical serve --config-file doc/zensical.toml
```

Open `localhost:8000` in your browser. The full set of LibreNMS
documentation is then available.

A `build` before the view is not necessary. The `serve` command builds
the documents. The server also updates the documents after each change
to the markdown, for example from another terminal.

### Viewing docs from another machine

By default, the server accepts connections only from the local machine.
For a build on a different machine, use this directive. The server then
listens on all interfaces:

```
zensical serve --config-file doc/zensical.toml --dev-addr=0.0.0.0:8000
```

WARNING: do not leave this server in operation. It is not a secure web
server. Use it only with correct host security.
