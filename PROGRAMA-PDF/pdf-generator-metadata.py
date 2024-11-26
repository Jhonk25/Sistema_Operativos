import tkinter as tk
from tkinter import ttk, messagebox, scrolledtext, filedialog
from reportlab.lib.pagesizes import letter
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer
from reportlab.lib.styles import getSampleStyleSheet
from datetime import datetime
import os
import PyPDF2
from reportlab.pdfbase import pdfdoc

class GeneradorPDFAvanzado:
    def __init__(self):
        # Crear ventana principal
        self.ventana = tk.Tk()
        self.ventana.title("Creador de PDF Avanzado")
        self.ventana.geometry("700x800")
        
        # Crear notebook para pestañas
        self.notebook = ttk.Notebook(self.ventana)
        self.notebook.pack(expand=True, fill='both')
        
        # Crear pestañas
        self.tab_crear = ttk.Frame(self.notebook)
        self.tab_verificar = ttk.Frame(self.notebook)
        
        self.notebook.add(self.tab_crear, text='Crear PDF')
        self.notebook.add(self.tab_verificar, text='Verificar Metadatos')
        
        self.crear_elementos_creacion()
        self.crear_elementos_verificacion()
        
    def crear_elementos_creacion(self):
        # Título
        ttk.Label(self.tab_crear, text="Título:").pack(pady=5)
        self.titulo = ttk.Entry(self.tab_crear, width=50)
        self.titulo.pack(pady=5)
        
        # Autor
        ttk.Label(self.tab_crear, text="Autor:").pack(pady=5)
        self.autor = ttk.Entry(self.tab_crear, width=50)
        self.autor.pack(pady=5)
        
        # Asunto
        ttk.Label(self.tab_crear, text="Asunto:").pack(pady=5)
        self.asunto = ttk.Entry(self.tab_crear, width=50)
        self.asunto.pack(pady=5)
        
        # Palabras clave
        ttk.Label(self.tab_crear, text="Palabras clave (separadas por comas):").pack(pady=5)
        self.keywords = ttk.Entry(self.tab_crear, width=50)
        self.keywords.pack(pady=5)
        
        # Creador
        ttk.Label(self.tab_crear, text="Creador (software):").pack(pady=5)
        self.creador = ttk.Entry(self.tab_crear, width=50)
        self.creador.insert(0, "PDF Generator v1.0")
        self.creador.pack(pady=5)
        
        # Contenido
        ttk.Label(self.tab_crear, text="Contenido:").pack(pady=5)
        self.contenido = scrolledtext.ScrolledText(self.tab_crear, width=50, height=10)
        self.contenido.pack(pady=5)
        
        # Botón para generar PDF
        ttk.Button(self.tab_crear, text="Crear PDF", command=self.generar_pdf).pack(pady=20)
        
    def crear_elementos_verificacion(self):
        # Frame para los controles
        frame_controles = ttk.Frame(self.tab_verificar)
        frame_controles.pack(pady=10, padx=10, fill='x')
        
        # Botón para seleccionar archivo
        ttk.Button(frame_controles, text="Seleccionar PDF", 
                  command=self.seleccionar_pdf).pack(side='left', padx=5)
        
        # Área de resultados
        self.resultado_metadatos = scrolledtext.ScrolledText(self.tab_verificar, 
                                                           width=50, height=20)
        self.resultado_metadatos.pack(pady=10, padx=10, fill='both', expand=True)
        
    def generar_pdf(self):
        try:
            # Pedir ubicación para guardar
            nombre_archivo = filedialog.asksaveasfilename(
                defaultextension=".pdf",
                filetypes=[("PDF files", "*.pdf")]
            )
            
            if not nombre_archivo:
                return
            
            # Crear el PDF con metadatos
            doc = SimpleDocTemplate(nombre_archivo, pagesize=letter)
            
            # Configurar metadatos
            doc.title = self.titulo.get()
            doc.author = self.autor.get()
            doc.subject = self.asunto.get()
            doc.keywords = self.keywords.get()
            doc.creator = self.creador.get()
            
            # Preparar el contenido
            estilos = getSampleStyleSheet()
            elementos = []
            
            # Agregar título
            elementos.append(Paragraph(self.titulo.get(), estilos['Title']))
            elementos.append(Spacer(1, 20))
            
            # Agregar contenido
            texto = self.contenido.get("1.0", tk.END)
            elementos.append(Paragraph(texto, estilos['Normal']))
            
            # Generar el PDF
            doc.build(elementos)
            
            # Crear archivo de texto con metadatos
            archivo_metadatos = os.path.splitext(nombre_archivo)[0] + "_metadata.txt"
            with open(archivo_metadatos, 'w', encoding='utf-8') as f:
                f.write("METADATOS DEL PDF:\n\n")
                f.write(f"Título: {self.titulo.get()}\n")
                f.write(f"Autor: {self.autor.get()}\n")
                f.write(f"Asunto: {self.asunto.get()}\n")
                f.write(f"Palabras clave: {self.keywords.get()}\n")
                f.write(f"Creador: {self.creador.get()}\n")
                f.write(f"Fecha de creación: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
            
            messagebox.showinfo("Éxito", f"PDF creado como {nombre_archivo}\nMetadatos guardados en {archivo_metadatos}")
            
            # Abrir el archivo PDF
            os.startfile(nombre_archivo)
            
        except Exception as e:
            messagebox.showerror("Error", f"Error al crear PDF: {str(e)}")
            
    def seleccionar_pdf(self):
        try:
            filename = filedialog.askopenfilename(
                filetypes=[("PDF files", "*.pdf")]
            )
            if filename:
                self.mostrar_metadatos(filename)
        except Exception as e:
            messagebox.showerror("Error", f"Error al abrir el archivo: {str(e)}")
            
    def mostrar_metadatos(self, filename):
        try:
            with open(filename, 'rb') as file:
                pdf = PyPDF2.PdfReader(file)
                info = pdf.metadata
                
                # Limpiar área de resultados
                self.resultado_metadatos.delete(1.0, tk.END)
                
                # Mostrar metadatos
                self.resultado_metadatos.insert(tk.END, "METADATOS DEL PDF:\n\n")
                
                if info:
                    for key, value in info.items():
                        # Eliminar el prefijo /
                        key = key.strip('/')
                        self.resultado_metadatos.insert(tk.END, 
                            f"{key}: {value}\n")
                else:
                    self.resultado_metadatos.insert(tk.END, 
                        "No se encontraron metadatos en el archivo.")
                    
        except Exception as e:
            messagebox.showerror("Error", 
                f"Error al leer metadatos: {str(e)}")
    
    def iniciar(self):
        self.ventana.mainloop()

# Iniciar la aplicación
if __name__ == "__main__":
    app = GeneradorPDFAvanzado()
    app.iniciar()