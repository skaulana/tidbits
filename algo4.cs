///
///   [ALGO-CLASS] HOMEWORK 4: STRONGLY CONNECTED COMPONENTS
///
///   Note - needed to increase stack size of generated executable to allow deep recursion
///   Syntax is: editbin /stack:70000000 algo4.exe
///
using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.IO;
using MyVertex = System.Collections.Generic.KeyValuePair<int, System.Collections.Generic.Stack<int>>;

namespace AlgoClassHW4
{
    /// <summary>
    /// [ALGO-CLASS] Directed graph with basic operations.
    /// </summary>
    class MyGraph
    {
        public Dictionary<int,Stack<int>> vertexlist;

        public MyGraph() { vertexlist = new Dictionary<int, Stack<int>>(); }
        public void NewVertex(int i) { vertexlist[i] = new Stack<int>(); }
        public bool HasVertex(int i) { if (vertexlist.ContainsKey(i) && vertexlist[i].Count > 0) return true; return false; }
        public int MaxVertex() { return vertexlist.Keys.Max(); }
        public void AddEdge(int i, int j)
        {
            if (!HasVertex(i)) NewVertex(i);
            if (!HasVertex(j)) NewVertex(j);
            vertexlist[i].Push(j);
        }
        public bool HasEdge(int i, int j)
        {
            if (!HasVertex(i)) return false;
            else return vertexlist[i].Contains(j);
        }
        public void RemoveVertex(int i)
        {
            if (!HasVertex(i)) return;
            NewVertex(i); // doesn't remove any incident edges...
        }
        public int CountVertices() { return vertexlist.Count; }
        public int CountEdges() { int count = 0; foreach (MyVertex v in vertexlist) { count += v.Value.Count; } return count; }
        public void WriteToConsole()
        {
            Console.ForegroundColor = ConsoleColor.Magenta;
            Console.WriteLine("Graph contents:");
            Console.ResetColor();

            foreach (MyVertex v in vertexlist)
            {
                Console.ForegroundColor = ConsoleColor.DarkMagenta;
                Console.Write("[{0}] => ", v.Key);

                int[] edges = v.Value.ToArray();
                for(int i = 0; i < edges.Length; i++)
                {
                    if (i != 0) { Console.Write(", "); }
                    Console.Write(edges[i]);
                }
                Console.WriteLine();
                Console.ResetColor();
            }
        }
    }

    /// <summary>
    /// [ALGO-CLASS] Workhorse for the problem.
    /// </summary>
    class Program
    {
        static bool VERBOSE = false;

        static int finishclock;
        static Dictionary<int, bool> unexplored;
        static Dictionary<int, int> finishtimes;

        static void Main(string[] args)
        {
            Console.ForegroundColor = ConsoleColor.Green;
            Console.WriteLine("Algo-Class HW #4");
            Console.WriteLine("----------------\n");
            Console.ResetColor();

            Console.Write("Read input from: ");

            #region Ask user for valid input file path...
            string rawinput;
            do
            {
                rawinput = Console.ReadLine();
                if (File.Exists(rawinput)) rawinput = File.ReadAllText(rawinput);
                else { Console.Write("Not found. Try again: "); rawinput = ""; }
            } while (rawinput == "");
            #endregion

            MyGraph g = new MyGraph();  // main graph
            MyGraph gr = new MyGraph(); // reverse graph

            #region Build graph (and reverse graph) from input file...
            string[] inputlines = rawinput.Split('\n');
            int readuntil = 0;
            while (readuntil < inputlines.Length)
            {
                if (inputlines[readuntil].Trim() == "") break; // white space line = stop parsing the file
                readuntil++;
            }

            for(int i = 0; i < readuntil; i++)
            {
                bool innumber = false; int vertex = -1; string currentnum = "";
                for (int j = 0; j < inputlines[i].Length; j++) // read characters in line
                {
                    char c = inputlines[i][j];
                    if (" \t\n\r\f".Contains(c)) // whitespace
                    {
                        if (innumber) // stop and parse as number
                        {
                            if (vertex == -1) vertex = int.Parse(currentnum.Trim());
                            else
                            {
                                g.AddEdge(vertex, int.Parse(currentnum.Trim()));
                                gr.AddEdge(int.Parse(currentnum.Trim()), vertex);
                            }
                            innumber = false;
                        }
                    }
                    else // number
                    {
                        if (!innumber) // start processing a new number
                        {
                            innumber = true; currentnum = "";
                        }
                        currentnum += c; // build char by char
                    }
                }
                if (innumber) // process last number for the line
                {
                    if (vertex != -1) // skip if we didn't see more than one number
                    {
                        g.AddEdge(vertex, int.Parse(currentnum.Trim()));
                        gr.AddEdge(int.Parse(currentnum.Trim()), vertex);                        
                    }
                }
            }
            #endregion

            Console.WriteLine("Added {0} edges across {1} vertices", g.CountEdges(), g.CountVertices());
            if (VERBOSE) g.WriteToConsole();

            Console.WriteLine("Running DFS loop on reverse graph...");
            Console.ForegroundColor = ConsoleColor.DarkGray;
            ResetUnexplored(gr);
            for (int i = gr.MaxVertex(); i > 0; i--)
            {
                if (unexplored.ContainsKey(i) && unexplored[i])
                {
                    if (VERBOSE) Console.WriteLine("Processing node: {0}", i);
                    MyDFS(gr, i);
                }
            }
            Console.ResetColor();

            // sort the dictionary in DESCENDING order with a LINQ query
            int[] finishorder = (from entry in finishtimes orderby entry.Value descending select entry.Key).ToArray();
            if (VERBOSE)
            {
                Console.WriteLine("Finish times computed as:");
                Console.ForegroundColor = ConsoleColor.DarkGray;
                for (int i = 0; i < finishorder.Length; i++)
                {
                    Console.WriteLine("[{0}] => {1}", i, finishorder[i]);
                }
                Console.ResetColor();
            }

            Console.WriteLine("Running DFS loop on main graph in finish time order...");
            ResetUnexplored(g);

            List<int> results = new List<int>();

            int leadernode;
            for (int i = 0; i < finishorder.Length; i++)
            {
                leadernode = finishorder[i];
                if (g.HasVertex(leadernode) && unexplored.ContainsKey(leadernode) && unexplored[leadernode])
                {
                    finishclock = 0;
                    Console.ForegroundColor = ConsoleColor.DarkGray;
                    Console.WriteLine("Processing leader node: {0}", leadernode);
                    MyDFS(g, leadernode);

                    Console.ForegroundColor = ConsoleColor.Blue;
                    Console.WriteLine("SCC of size {0} found", finishclock);
                    Console.ResetColor();

                    results.Add(finishclock);
                }
            }

            Console.WriteLine();
            Console.WriteLine("Final results for SCC sizes");
            Console.WriteLine("---------------------------");

            results.Sort();
            for (int i = 0; i < results.Count; i++)
            {
                if (i != 0) Console.Write(", ");
                Console.Write(results[i]);
            }

            Console.ReadKey();
        }

        static void ResetUnexplored(MyGraph graph)
        {
            unexplored = new Dictionary<int,bool>();
            finishtimes = new Dictionary<int,int>();
            foreach (MyVertex v in graph.vertexlist)
            {
                unexplored[v.Key] = true;
            }
            finishclock = 0;
        }

        static void MyDFS(MyGraph graph, int v)
        {
            if (!graph.HasVertex(v))
            {
                //Console.ForegroundColor = ConsoleColor.Red;
                //Console.WriteLine("Couldn't find node {0} in the DFS graph", v);
                //Console.ResetColor();
            }
            else
            {
                unexplored[v] = false;

                int nextnode;
                while (graph.HasVertex(v))
                {
                    nextnode = graph.vertexlist[v].Pop();
                    if (unexplored.ContainsKey(nextnode) && unexplored[nextnode]) MyDFS(graph, nextnode);
                }

                finishclock++;
                finishtimes[v] = finishclock;
            }
        }
    }
}
