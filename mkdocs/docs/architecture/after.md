# Symfony AI Agent Toolbox Exception Class Hierarchy (Improved)

## Mermaid Class Diagram

```mermaid
classDiagram
    %% Base PHP Exception Classes
    class Throwable {
        <<built-in>>
    }
    class Exception {
        <<built-in>>
    }
    class RuntimeException {
        <<built-in>>
    }

    %% Base Symfony AI Exceptions
    class BaseExceptionInterface {
        <<interface>>
    }
    class InvalidArgumentException
    
    %% Toolbox Exceptions
    class ExceptionInterface {
        <<interface>>
    }
    class ToolException
    class ToolConfigurationException
    class ToolNotFoundException
    class ToolExecutionException
    class InvalidToolCallArgumentsException

    %% Inheritance relationships
    Throwable <|-- Exception
    Exception <|-- RuntimeException
    Exception <|-- InvalidArgumentException
    
    BaseExceptionInterface <|-- ExceptionInterface
    
    InvalidArgumentException <|-- ToolException
    ExceptionInterface <|-- ToolException
    
    InvalidArgumentException <|-- ToolConfigurationException
    ExceptionInterface <|-- ToolConfigurationException
    
    RuntimeException <|-- ToolNotFoundException
    ExceptionInterface <|-- ToolNotFoundException
    
    RuntimeException <|-- ToolExecutionException
    ExceptionInterface <|-- ToolExecutionException
    
    RuntimeException <|-- InvalidToolCallArgumentsException
    ExceptionInterface <|-- InvalidToolCallArgumentsException
```

## Changes from Previous Version

The improved version removed `ToolExecutionExceptionInterface` and refactored the hierarchy:
- All concrete exceptions now directly implement `ExceptionInterface`
- `ToolExecutionException` and `InvalidToolCallArgumentsException` provide execution results via `getToolCallResult()` method
- Simplified interface hierarchy while maintaining the same functionality
