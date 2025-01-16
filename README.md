# ash

ash is an AI-operated Linux shell that allows users to interact with their operating system using natural language commands. Instead of typing traditional Linux commands, users can communicate with a ChatGPT-powered natural language assistant who will facilitate the execution of the proper commands on behalf of the user as requested.

## Usage

To use ash, simply open a terminal and start typing natural language commands. The assistant will interpret your commands and execute the appropriate Linux commands on your behalf. For example, instead of typing `ls -l` to list the contents of a directory, you can simply type something like "list files" and ash will know to execute the `ls -l` command for you.

## Installation

Clone the GitHub Repo or extract the ZIP file to your local machine. Navigate to the directory where you have saved the files and run the following commands to initialize composer:

```bash
composer install
chmod +x ash
ln -s ./ash /usr/local/bin/ash
```
## Running

To run ash, simply execute the following command in the terminal:

```bash
ash
```
Now begin talking to your AI assistant and watch as it executes your commands for you!

## Contributing

If you would like to contribute to ash, please submit a pull request with your changes. We welcome contributions of all kinds, including bug fixes, new features, and documentation improvements.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.