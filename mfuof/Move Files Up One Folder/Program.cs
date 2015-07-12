using System;
using System.IO;
using System.Windows.Forms;

namespace Move_Files_Up_One_Folder
{
    static class Program
    {
        /// <summary>
        /// The main entry point for the application.
        /// </summary>
        [STAThread]
        static void Main(string[] args)
        {
            foreach (string arg in args)  // take each command line arg, and attempt to move it up one folder to ..
            {
                if (File.Exists(arg))
                {
                    try
                    {
                        FileInfo info = new FileInfo(arg);
                        info.MoveTo(info.Directory.Parent.FullName + '\\' + info.Name);
                    }
                    catch
                    {
                        // move failed - let the user know
                        MessageBox.Show("Could not move " + arg + ".", "Error moving file", MessageBoxButtons.OK, MessageBoxIcon.Exclamation);
                    }
                }
            }
        }
    }
}
