# ILIAS Quode Question Score Integration Plugin

**Author**:   Frank Bauer <frank.bauer@fau.de>

**Version**:  1.0.3

**Company**:  Computer Graphics Group Erlangen

**Supports**: ILIAS 5.1 - 5.3

## License
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

## Installation
1. Copy the `
CodeQuestionScoreIntegration` directory to your ILIAS installation at the following path 
(create subdirectories, if neccessary):
`Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CodeQuestionScoreIntegration`

2. Go to Administration > Plugins

3. Choose **Update** for the `CodeQuestionScoreIntegration` plugin
4. Choose **Activate** for the `CodeQuestionScoreIntegration` plugin
5. Choose **Refresh** for the `CodeQuestionScoreIntegration` plugin languages

There is nothing to configure for this plugin.

## Version History
### Version 1.0.3
* Bugfix for ordering export
### Version 1.0.2
* Exporting all code blocks to seperate files.
* Export json for vertical/horizontal ordering question type